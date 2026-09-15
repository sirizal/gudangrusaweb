<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\BudgetStatus;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BudgetService
{
    /**
     * Create a draft budget from validated data.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Budget
    {
        $lines = $this->normalizeLines($data['lines'] ?? []);
        $this->assertLinesValid($lines);

        return DB::transaction(function () use ($data, $lines): Budget {
            $budget = Budget::create([
                'company_id' => $data['company_id'] ?? null,
                'budget_code' => $data['budget_code'],
                'budget_name' => $data['budget_name'],
                'fiscal_year_id' => $data['fiscal_year_id'],
                'version' => 'V1',
                'status' => BudgetStatus::Draft,
                'description' => $data['description'] ?? null,
            ]);

            $budget->recordAudit('budget_created', ['budget_code' => $budget->budget_code]);
            $this->createLines($budget, $lines);

            return $budget->load('lines.months');
        });
    }

    /**
     * Update the header and lines of an editable budget.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Budget $budget, array $data, ?User $actor = null): Budget
    {
        $this->assertStatus($budget, BudgetStatus::Draft, BudgetStatus::Rejected);

        $lines = $this->normalizeLines($data['lines'] ?? []);
        $this->assertLinesValid($lines);

        return DB::transaction(function () use ($budget, $data, $lines): Budget {
            $budget->update([
                'budget_name' => $data['budget_name'],
                'description' => $data['description'] ?? null,
            ]);

            $budget->lines()->delete();
            $this->createLines($budget, $lines);

            $budget->recordAudit('budget_updated', ['budget_code' => $budget->budget_code]);

            return $budget->load('lines.months');
        });
    }

    public function submit(Budget $budget, ?User $actor = null): Budget
    {
        $this->assertStatus($budget, BudgetStatus::Draft, BudgetStatus::Rejected);

        $budget->update([
            'status' => BudgetStatus::Submitted,
            'submitted_by' => $actor?->id ?? auth()->id(),
            'submitted_at' => now(),
        ]);
        $budget->recordAudit('budget_submitted', ['budget_code' => $budget->budget_code]);

        return $budget;
    }

    public function approve(Budget $budget, ?User $actor = null): Budget
    {
        $this->assertStatus($budget, BudgetStatus::Submitted);

        DB::transaction(function () use ($budget, $actor): void {
            $budget->update([
                'status' => BudgetStatus::Approved,
                'approved_by' => $actor?->id ?? auth()->id(),
                'approved_at' => now(),
                'is_active' => true,
            ]);

            Budget::query()
                ->where('fiscal_year_id', $budget->fiscal_year_id)
                ->whereKeyNot($budget->getKey())
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $budget->recordAudit('budget_approved', ['budget_code' => $budget->budget_code]);
        });

        return $budget;
    }

    public function reject(Budget $budget, ?User $actor = null): Budget
    {
        $this->assertStatus($budget, BudgetStatus::Submitted);

        $budget->update([
            'status' => BudgetStatus::Rejected,
            'approved_by' => $actor?->id ?? auth()->id(),
            'approved_at' => now(),
        ]);
        $budget->recordAudit('budget_rejected', ['budget_code' => $budget->budget_code]);

        return $budget;
    }

    public function lock(Budget $budget, ?User $actor = null): Budget
    {
        $this->assertStatus($budget, BudgetStatus::Approved);

        $budget->update(['status' => BudgetStatus::Locked]);
        $budget->recordAudit('budget_locked', ['budget_code' => $budget->budget_code]);

        return $budget;
    }

    public function activate(Budget $budget): Budget
    {
        $this->assertStatus($budget, BudgetStatus::Approved, BudgetStatus::Locked);

        DB::transaction(function () use ($budget): void {
            Budget::query()
                ->where('fiscal_year_id', $budget->fiscal_year_id)
                ->whereKeyNot($budget->getKey())
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $budget->update(['is_active' => true]);
            $budget->recordAudit('budget_activated', ['budget_code' => $budget->budget_code]);
        });

        return $budget;
    }

    /**
     * Create a new draft version copying the lines of an approved or locked
     * budget. The previous version is left untouched.
     */
    public function revise(Budget $budget, ?User $actor = null): Budget
    {
        $this->assertStatus($budget, BudgetStatus::Approved, BudgetStatus::Locked);

        return DB::transaction(function () use ($budget): Budget {
            $version = $this->nextVersion($budget->version);

            $revision = Budget::create([
                'company_id' => $budget->company_id,
                'budget_code' => $budget->budget_code.'-'.$version,
                'budget_name' => $budget->budget_name,
                'fiscal_year_id' => $budget->fiscal_year_id,
                'version' => $version,
                'status' => BudgetStatus::Draft,
                'is_active' => false,
                'description' => 'Revision of '.$budget->budget_code,
            ]);

            foreach ($budget->lines as $line) {
                $copyLine = $revision->lines()->create([
                    'account_id' => $line->account_id,
                    'cost_center_id' => $line->cost_center_id,
                    'department_id' => $line->department_id,
                    'project_id' => $line->project_id,
                    'description' => $line->description,
                    'annual_amount' => $line->annual_amount,
                ]);

                foreach ($line->months as $month) {
                    $copyLine->months()->create([
                        'month' => $month->month,
                        'amount' => $month->amount,
                    ]);
                }
            }

            $revision->recordAudit('budget_revision_created', ['source' => $budget->budget_code]);

            return $revision->load('lines.months');
        });
    }

    /**
     * Compare the active budget against posted actuals for a fiscal year.
     *
     * Ranges: "monthly" compares one period's budget vs actual, "ytd" sums
     * budget months up to the selected period, and "full_year" uses annual
     * amounts. Each row respects the budget's cost center / department /
     * project dimensions, and optional report-level dimension filters.
     *
     * @return array<string, mixed>
     */
    public function budgetVsActual(
        int $fiscalYearId,
        ?int $periodNumber = null,
        string $range = 'monthly',
        ?int $costCenterId = null,
        ?int $departmentId = null,
        ?int $projectId = null,
    ): array {
        $fiscalYear = FiscalYear::with('accountingPeriods')->findOrFail($fiscalYearId);

        $budget = Budget::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->activeBudget()
            ->first()
            ?? Budget::query()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->whereIn('status', [BudgetStatus::Approved, BudgetStatus::Locked])
                ->orderByDesc('version')
                ->first();

        if (! $budget) {
            return [
                'fiscal_year' => $fiscalYear,
                'budget' => null,
                'period' => null,
                'range' => $range,
                'rows' => [],
                'totals' => ['budget' => 0.0, 'actual' => 0.0, 'variance' => 0.0],
            ];
        }

        [$from, $to, $period, $monthLimit] = $this->resolveBudgetWindow($fiscalYear, $periodNumber, $range);

        $actualMap = $this->actualSums($fiscalYear, $from, $to);

        $lines = $budget->lines()
            ->with('account', 'months')
            ->when($costCenterId, fn ($query, $id) => $query->where('cost_center_id', $id))
            ->when($departmentId, fn ($query, $id) => $query->where('department_id', $id))
            ->when($projectId, fn ($query, $id) => $query->where('project_id', $id))
            ->get();

        $rows = [];
        $totals = ['budget' => 0.0, 'actual' => 0.0, 'variance' => 0.0];

        foreach ($lines as $line) {
            $budgetAmount = $this->budgetWindowAmount($line, $monthLimit, $range);
            $actual = $this->actualForLine($line, $actualMap);
            $variance = $this->variance($line->account, $budgetAmount, $actual);

            $totals['budget'] = $this->sum($totals['budget'], $budgetAmount);
            $totals['actual'] = $this->sum($totals['actual'], $actual);
            $totals['variance'] = $this->sum($totals['variance'], $variance);

            $rows[] = [
                'account_code' => $line->account->account_code,
                'account_name' => $line->account->account_name,
                'cost_center_name' => $line->costCenter?->name,
                'department_name' => $line->department?->name,
                'project_name' => $line->project?->name,
                'budget' => $budgetAmount,
                'actual' => $actual,
                'variance' => $variance,
                'variance_pct' => $this->variancePercent($budgetAmount, $variance),
            ];
        }

        $totals['variance_pct'] = $this->variancePercent($totals['budget'], $totals['variance']);

        return [
            'fiscal_year' => $fiscalYear,
            'budget' => $budget,
            'period' => $period,
            'range' => $range,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: ?AccountingPeriod, 3: ?int}
     */
    private function resolveBudgetWindow(FiscalYear $fiscalYear, ?int $periodNumber, string $range): array
    {
        if ($range === 'full_year') {
            return [$fiscalYear->start_date->toDateString(), $fiscalYear->end_date->toDateString(), null, null];
        }

        $period = $fiscalYear->accountingPeriods->firstWhere('period_number', $periodNumber)
            ?? throw new InvalidArgumentException('Accounting period not found for this fiscal year.');

        if ($range === 'ytd') {
            return [$fiscalYear->start_date->toDateString(), $period->end_date->toDateString(), $period, $period->period_number];
        }

        return [$period->start_date->toDateString(), $period->end_date->toDateString(), $period, $period->period_number];
    }

    /**
     * @return array<string, array{0: float, 1: float}>
     */
    private function actualSums(FiscalYear $fiscalYear, string $from, string $to): array
    {
        $periodIds = $fiscalYear->accountingPeriods->pluck('id');

        return DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entries.accounting_period_id', $periodIds)
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->where('journal_entries.journal_date', '>=', $from)
            ->where('journal_entries.journal_date', '<=', $to)
            ->groupBy(['journal_entry_lines.account_id', 'journal_entry_lines.cost_center_id', 'journal_entry_lines.department_id', 'journal_entry_lines.project_id'])
            ->get([
                'journal_entry_lines.account_id as account_id',
                'journal_entry_lines.cost_center_id as cost_center_id',
                'journal_entry_lines.department_id as department_id',
                'journal_entry_lines.project_id as project_id',
                DB::raw('SUM(journal_entry_lines.debit) as debit'),
                DB::raw('SUM(journal_entry_lines.credit) as credit'),
            ])
            ->mapWithKeys(function ($row): array {
                $key = $this->dimensionKey($row->account_id, $row->cost_center_id, $row->department_id, $row->project_id);

                return [$key => [(float) $row->debit, (float) $row->credit]];
            })
            ->all();
    }

    /**
     * @param  array<string, array{0: float, 1: float}>  $actualMap
     */
    private function actualForLine(BudgetLine $line, array $actualMap): float
    {
        $key = $this->dimensionKey($line->account_id, $line->cost_center_id, $line->department_id, $line->project_id);
        [$debit, $credit] = $actualMap[$key] ?? [0.0, 0.0];

        if ($line->account->normal_balance === NormalBalance::Debit) {
            return $this->sum($debit, -1 * $credit);
        }

        return $this->sum($credit, -1 * $debit);
    }

    /**
     * @param  array<int, mixed>  $line
     */
    private function budgetWindowAmount($line, ?int $monthLimit, string $range): float
    {
        if ($range === 'full_year') {
            return $this->amount($line->annual_amount);
        }

        if ($monthLimit === null) {
            return 0.0;
        }

        if ($range === 'monthly') {
            return $this->amount($line->months->firstWhere('month', $monthLimit)?->amount);
        }

        return $this->amount($line->months->where('month', '<=', $monthLimit)->sum('amount'));
    }

    private function variance(Account $account, float $budgetAmount, float $actual): float
    {
        return match ($account->account_type) {
            AccountType::Revenue, AccountType::OtherIncome => $actual - $budgetAmount,
            default => $budgetAmount - $actual,
        };
    }

    private function variancePercent(float $budgetAmount, float $variance): ?float
    {
        if ($budgetAmount == 0) {
            return null;
        }

        return $this->amount($variance / $budgetAmount * 100);
    }

    private function dimensionKey(?int $accountId, ?int $costCenterId, ?int $departmentId, ?int $projectId): string
    {
        return implode('|', [(string) $accountId, (string) $costCenterId, (string) $departmentId, (string) $projectId]);
    }

    private function sum(float ...$values): float
    {
        return $this->amount(array_sum($values));
    }

    private function amount(mixed $value): float
    {
        return round((float) $value, 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function createLines(Budget $budget, array $lines): void
    {
        foreach ($lines as $line) {
            $annual = 0.0;

            $budgetLine = $budget->lines()->create([
                'account_id' => $line['account_id'],
                'cost_center_id' => $line['cost_center_id'] ?? null,
                'department_id' => $line['department_id'] ?? null,
                'project_id' => $line['project_id'] ?? null,
                'description' => $line['description'] ?? null,
                'annual_amount' => 0,
            ]);

            foreach (range(1, 12) as $month) {
                $amount = (float) ($line['months'][$month] ?? 0);
                $annual += $amount;

                $budgetLine->months()->create([
                    'month' => $month,
                    'amount' => $amount,
                ]);
            }

            $budgetLine->update(['annual_amount' => $annual]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function assertLinesValid(array $lines): void
    {
        if ($lines === []) {
            throw new InvalidArgumentException('A budget must contain at least one line.');
        }

        $seen = [];

        foreach ($lines as $line) {
            $account = Account::find($line['account_id'] ?? null);

            if (! $account || ! $account->is_active) {
                throw new InvalidArgumentException('Account does not exist or is inactive.');
            }

            if ($account->is_group || ! $account->is_postable) {
                throw new InvalidArgumentException('Budget lines require a postable, non-group account.');
            }

            $key = implode('|', [
                $line['account_id'],
                $line['cost_center_id'] ?? '',
                $line['department_id'] ?? '',
                $line['project_id'] ?? '',
            ]);

            if (isset($seen[$key])) {
                throw new InvalidArgumentException('Duplicate budget line for the same account and dimensions.');
            }

            $seen[$key] = true;

            foreach (range(1, 12) as $month) {
                $amount = (float) ($line['months'][$month] ?? 0);

                if ($amount < 0) {
                    throw new InvalidArgumentException('Budget amounts must not be negative.');
                }
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLines(array $lines): array
    {
        $normalized = [];

        foreach (array_values(array_filter($lines, fn (array $line): bool => isset($line['account_id']))) as $line) {
            $line['months'] = $this->monthsFromLine($line);
            $normalized[] = $line;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array<int, float>
     */
    private function monthsFromLine(array $line): array
    {
        if (isset($line['months']) && is_array($line['months'])) {
            return $line['months'];
        }

        $months = [];

        foreach (range(1, 12) as $month) {
            $months[$month] = (float) ($line['month_'.$month] ?? 0);
        }

        return $months;
    }

    private function assertStatus(Budget $budget, BudgetStatus ...$expected): void
    {
        if (! in_array($budget->status, $expected, true)) {
            throw new InvalidArgumentException('Budget '.$budget->budget_code.' cannot be processed in its current status.');
        }
    }

    private function nextVersion(string $version): string
    {
        if (preg_match('/^V(\d+)$/', $version, $matches)) {
            return 'V'.((int) $matches[1] + 1);
        }

        return 'V2';
    }
}
