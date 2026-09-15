<?php

namespace App\Services\Accounting;

use App\Enums\JournalStatus;
use App\Models\Budget;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        private readonly FinancialStatementService $statements,
        private readonly BudgetService $budgets,
    ) {}

    /**
     * Compute the headline financial key performance indicators.
     *
     * @return array<string, mixed>
     */
    public function kpis(): array
    {
        $fiscalYear = FiscalYear::query()->current()->first() ?? FiscalYear::query()->first();

        if (! $fiscalYear) {
            return [
                'fiscal_year' => null,
                'revenue' => 0,
                'gross_profit' => 0,
                'operating_profit' => 0,
                'net_profit' => 0,
                'cash_balance' => 0,
                'accounts_receivable' => 0,
                'accounts_payable' => 0,
                'budget_utilization' => null,
                'ytd_revenue' => 0,
                'ytd_expense' => 0,
            ];
        }

        $income = $this->statements->incomeStatement($fiscalYear->id);
        $balance = $this->statements->balanceSheet($fiscalYear->id);

        $today = today()->toDateString();
        $ytd = $this->statements->incomeStatement($fiscalYear->id, asOfDate: $today);

        return [
            'fiscal_year' => $fiscalYear,
            'revenue' => $income['revenue'],
            'gross_profit' => $income['gross_profit'],
            'operating_profit' => $income['operating_profit'],
            'net_profit' => $income['net_profit'],
            'cash_balance' => $balance['cash'],
            'accounts_receivable' => $balance['accounts_receivable'],
            'accounts_payable' => $balance['accounts_payable'],
            'budget_utilization' => $this->budgetUtilization($fiscalYear),
            'ytd_revenue' => $ytd['revenue'],
            'ytd_expense' => $ytd['cost_of_sales'] + $ytd['operating_expenses'] + $ytd['other_expenses'],
        ];
    }

    /**
     * Monthly revenue, expense, and profit series for the current fiscal year.
     *
     * @return array{labels: array<int, string>, revenue: array<int, float>, expense: array<int, float>, profit: array<int, float>}
     */
    public function monthlyPerformance(): array
    {
        $fiscalYear = FiscalYear::query()->current()->first() ?? FiscalYear::query()->first();

        $labels = [];
        $revenue = [];
        $expense = [];
        $profit = [];

        if (! $fiscalYear) {
            return compact('labels', 'revenue', 'expense', 'profit');
        }

        foreach ($fiscalYear->accountingPeriods()->orderBy('period_number')->get() as $period) {
            $statement = $this->statements->incomeStatement($fiscalYear->id, periodNumber: $period->period_number);

            $labels[] = $period->period_name;
            $revenue[] = $statement['revenue'];
            $expense[] = $statement['cost_of_sales'] + $statement['operating_expenses'] + $statement['other_expenses'];
            $profit[] = $statement['net_profit'];
        }

        return compact('labels', 'revenue', 'expense', 'profit');
    }

    /**
     * Monthly budget and actual series from the current fiscal year.
     *
     * @return array{labels: array<int, string>, budget: array<int, float>, actual: array<int, float>}
     */
    public function budgetVsActualSeries(): array
    {
        $fiscalYear = FiscalYear::query()->current()->first() ?? FiscalYear::query()->first();

        $labels = [];
        $budget = [];
        $actual = [];

        if (! $fiscalYear) {
            return compact('labels', 'budget', 'actual');
        }

        $activeBudget = $this->activeBudgetFor($fiscalYear);

        $totals = $this->monthlySignedTotals($fiscalYear, $activeBudget?->lines->pluck('account_id')->all());

        foreach ($fiscalYear->accountingPeriods()->orderBy('period_number')->get() as $period) {
            $labels[] = $period->period_name;
            $budget[] = $this->budgetForMonth($activeBudget, $period->period_number);
            $actual[] = $totals[$period->period_number] ?? 0.0;
        }

        return compact('labels', 'budget', 'actual');
    }

    /**
     * Monthly cash balance trend for the current fiscal year.
     *
     * @return array{labels: array<int, string>, cash: array<int, float>}
     */
    public function cashFlowTrend(): array
    {
        $fiscalYear = FiscalYear::query()->current()->first() ?? FiscalYear::query()->first();

        $labels = [];
        $cash = [];

        if (! $fiscalYear) {
            return compact('labels', 'cash');
        }

        foreach ($fiscalYear->accountingPeriods()->orderBy('period_number')->get() as $period) {
            $labels[] = $period->period_name;

            $closingBalance = $this->statements->balanceSheet($fiscalYear->id, periodNumber: $period->period_number);
            $cash[] = $closingBalance['cash'];
        }

        return compact('labels', 'cash');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function alerts(): array
    {
        $fiscalYear = FiscalYear::query()->current()->first() ?? FiscalYear::query()->first();

        if (! $fiscalYear) {
            return [];
        }

        $alerts = [];
        $kpis = $this->kpis();

        if ($kpis['cash_balance'] < 0) {
            $alerts[] = ['level' => 'danger', 'title' => 'Negative cash balance', 'detail' => 'Cash is '.number_format($kpis['cash_balance']), 'created_at' => null];
        }

        $unposted = JournalEntry::query()
            ->whereIn('accounting_period_id', $fiscalYear->accountingPeriods->pluck('id')->all())
            ->whereIn('status', [JournalStatus::Draft, JournalStatus::Submitted, JournalStatus::Approved])
            ->count();

        if ($unposted > 0) {
            $alerts[] = ['level' => 'warning', 'title' => 'Unposted journals', 'detail' => $unposted.' journal entries are not yet posted.', 'created_at' => null];
        }

        $overBudget = $this->budgets->budgetVsActual($fiscalYear->id, range: 'full_year')['rows'] ?? [];
        $overBudget = collect($overBudget)->filter(fn (array $row): bool => $row['variance'] < 0)->count();

        if ($overBudget > 0) {
            $alerts[] = ['level' => 'danger', 'title' => 'Over budget', 'detail' => $overBudget.' accounts exceed their budget.', 'created_at' => null];
        }

        return $alerts;
    }

    private function activeBudgetFor(FiscalYear $fiscalYear): ?Budget
    {
        return Budget::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->activeBudget()
            ->first()
            ?? Budget::query()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->whereIn('status', ['approved', 'locked'])
                ->orderByDesc('version')
                ->first();
    }

    private function budgetForMonth(?Budget $budget, int $month): float
    {
        if (! $budget) {
            return 0.0;
        }

        return (float) $budget->lines->flatMap->months
            ->filter(fn ($m): bool => $m->month === $month)
            ->sum('amount');
    }

    private function monthlySignedTotals(FiscalYear $fiscalYear, ?array $accountIds = null): array
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->whereIn('journal_entries.accounting_period_id', $fiscalYear->accountingPeriods->pluck('id'))
            ->where('journal_entries.status', 'posted');

        if ($accountIds) {
            $query->whereIn('journal_entry_lines.account_id', $accountIds);
        }

        $rows = $query->get([
            'journal_entries.accounting_period_id',
            'accounts.normal_balance',
            'journal_entry_lines.debit',
            'journal_entry_lines.credit',
        ]);

        $byPeriod = [];

        foreach ($rows as $row) {
            $periodNumber = (int) $fiscalYear->accountingPeriods->firstWhere('id', $row->accounting_period_id)?->period_number;
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;

            if (! isset($byPeriod[$periodNumber])) {
                $byPeriod[$periodNumber] = 0.0;
            }

            $byPeriod[$periodNumber] += $row->normal_balance === 'debit' ? $debit - $credit : $credit - $debit;
        }

        return $byPeriod;
    }

    private function budgetUtilization(FiscalYear $fiscalYear): ?float
    {
        $budget = $this->activeBudgetFor($fiscalYear);

        if (! $budget) {
            return null;
        }

        $budgetTotal = (float) $budget->lines->sum('annual_amount');

        if ($budgetTotal > 0) {
            $series = $this->budgetVsActualSeries();

            return round(array_sum($series['actual']) / $budgetTotal * 100, 2);
        }

        return null;
    }
}
