<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\FinancialStatementLine;
use App\Models\FiscalYear;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FinancialStatementService
{
    /**
     * Generate a trial balance for a fiscal year, optionally restricted to a
     * single accounting period or an "as of" date.
     *
     * @return array<string, mixed>
     */
    public function trialBalance(int $fiscalYearId, ?int $periodNumber = null, ?string $asOfDate = null): array
    {
        $fiscalYear = FiscalYear::with('accountingPeriods')->findOrFail($fiscalYearId);
        [$reportStart, $reportEnd, $period] = $this->resolveReportWindow($fiscalYear, $periodNumber, $asOfDate);

        $periodIds = $fiscalYear->accountingPeriods->pluck('id');

        $opening = $this->balanceQuery($periodIds, $reportStart, reportEnd: null, beforeDate: true)
            ->groupBy('journal_entry_lines.account_id')
            ->select('journal_entry_lines.account_id', DB::raw('SUM(journal_entry_lines.debit) as debit'), DB::raw('SUM(journal_entry_lines.credit) as credit'))
            ->get()
            ->keyBy('account_id');

        $movement = $this->balanceQuery($periodIds, $reportStart, $reportEnd)
            ->groupBy('journal_entry_lines.account_id')
            ->select('journal_entry_lines.account_id', DB::raw('SUM(journal_entry_lines.debit) as debit'), DB::raw('SUM(journal_entry_lines.credit) as credit'))
            ->get()
            ->keyBy('account_id');

        $rows = [];

        Account::query()
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get()
            ->each(function (Account $account) use ($opening, $movement, &$rows): void {
                $open = $opening->get($account->id);
                $move = $movement->get($account->id);

                $openingDebit = $this->amount($open?->debit);
                $openingCredit = $this->amount($open?->credit);
                $movementDebit = $this->amount($move?->debit);
                $movementCredit = $this->amount($move?->credit);

                $rows[] = [
                    'account_code' => $account->account_code,
                    'account_name' => $account->account_name,
                    'opening_debit' => $openingDebit,
                    'opening_credit' => $openingCredit,
                    'movement_debit' => $movementDebit,
                    'movement_credit' => $movementCredit,
                    'closing_debit' => $this->amount($openingDebit + $movementDebit),
                    'closing_credit' => $this->amount($openingCredit + $movementCredit),
                ];
            });

        $totals = [
            'opening_debit' => $this->amount(collect($rows)->sum('opening_debit')),
            'opening_credit' => $this->amount(collect($rows)->sum('opening_credit')),
            'movement_debit' => $this->amount(collect($rows)->sum('movement_debit')),
            'movement_credit' => $this->amount(collect($rows)->sum('movement_credit')),
            'closing_debit' => $this->amount(collect($rows)->sum('closing_debit')),
            'closing_credit' => $this->amount(collect($rows)->sum('closing_credit')),
        ];

        return [
            'fiscal_year' => $fiscalYear,
            'period' => $period,
            'from' => $reportStart,
            'to' => $reportEnd,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Generate a general ledger for a single account with a running balance
     * that respects the account's normal balance.
     *
     * @return array<string, mixed>
     */
    public function generalLedger(int $accountId, int $fiscalYearId, string $from, string $to, ?int $costCenterId = null, ?int $departmentId = null, ?int $projectId = null): array
    {
        $account = Account::findOrFail($accountId);

        if (! $account->is_postable) {
            throw new InvalidArgumentException('The selected account is not postable.');
        }

        $fiscalYear = FiscalYear::with('accountingPeriods')->findOrFail($fiscalYearId);
        $periodIds = $fiscalYear->accountingPeriods->pluck('id');

        $query = $this->ledgerQuery($periodIds, $accountId, $costCenterId, $departmentId, $projectId);

        $opening = $this->amount(
            $query->where('journal_entries.journal_date', '<', $from)->sum(DB::raw('journal_entry_lines.debit - journal_entry_lines.credit')),
        );

        $movement = $this->ledgerQuery($periodIds, $accountId, $costCenterId, $departmentId, $projectId)
            ->where('journal_entries.journal_date', '>=', $from)
            ->where('journal_entries.journal_date', '<=', $to)
            ->orderBy('journal_entries.journal_date')
            ->orderBy('journal_entries.journal_number')
            ->get([
                'journal_entries.journal_date',
                'journal_entries.journal_number',
                'journal_entries.description',
                'journal_entry_lines.debit',
                'journal_entry_lines.credit',
            ]);

        $rows = [];
        $balance = $opening;

        foreach ($movement as $line) {
            $debit = $this->amount($line->debit);
            $credit = $this->amount($line->credit);

            if ($account->normal_balance === NormalBalance::Debit) {
                $balance = $this->amount($balance + $debit - $credit);
            } else {
                $balance = $this->amount($balance + $credit - $debit);
            }

            $rows[] = [
                'date' => $line->journal_date,
                'journal_number' => $line->journal_number,
                'description' => $line->description,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $balance,
            ];
        }

        return [
            'account' => $account,
            'fiscal_year' => $fiscalYear,
            'from' => $from,
            'to' => $to,
            'opening_balance' => $opening,
            'rows' => $rows,
            'closing_balance' => $balance,
        ];
    }

    /**
     * Generate an income statement for a report window.
     *
     * Account-backed sections come from the configured FinancialStatementLine
     * mapping when available, otherwise falling back to account-type
     * groupings. Gross profit, operating profit, profit before tax and net
     * profit are derived from those sections.
     *
     * @return array<string, mixed>
     */
    public function incomeStatement(int $fiscalYearId, ?int $periodNumber = null, ?string $asOfDate = null): array
    {
        $fiscalYear = FiscalYear::with('accountingPeriods')->findOrFail($fiscalYearId);
        [$reportStart, $reportEnd, $period] = $this->resolveReportWindow($fiscalYear, $periodNumber, $asOfDate);

        $movement = $this->movementSums($fiscalYear, $reportStart, $reportEnd);

        $revenue = $this->sectionAmount('IS-REV', $movement, [AccountType::Revenue]);
        $costOfSales = $this->sectionAmount('IS-COS', $movement, [AccountType::CostOfSales]);
        $operatingExpenses = $this->sectionAmount('IS-OPEX', $movement, [AccountType::Expense]);
        $otherIncome = $this->sectionAmount('IS-OI', $movement, [AccountType::OtherIncome]);
        $otherExpenses = $this->sectionAmount('IS-OE', $movement, [AccountType::OtherExpense]);
        $incomeTax = $this->sectionAmount('IS-TAX', $movement, [AccountType::OtherExpense]);

        $grossProfit = $this->amount($revenue - $costOfSales);
        $operatingProfit = $this->amount($grossProfit - $operatingExpenses);
        $profitBeforeTax = $this->amount($operatingProfit + $otherIncome - $otherExpenses);
        $netProfit = $this->amount($profitBeforeTax - $incomeTax);

        return [
            'fiscal_year' => $fiscalYear,
            'period' => $period,
            'from' => $reportStart,
            'to' => $reportEnd,
            'revenue' => $revenue,
            'cost_of_sales' => $costOfSales,
            'gross_profit' => $grossProfit,
            'operating_expenses' => $operatingExpenses,
            'operating_profit' => $operatingProfit,
            'other_income' => $otherIncome,
            'other_expenses' => $otherExpenses,
            'profit_before_tax' => $profitBeforeTax,
            'income_tax' => $incomeTax,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Generate a balance sheet as at the report window end.
     *
     * The equity section is closed with the net profit so that
     * Total Assets = Total Liabilities + Total Equity.
     *
     * @return array<string, mixed>
     */
    public function balanceSheet(int $fiscalYearId, ?int $periodNumber = null, ?string $asOfDate = null): array
    {
        $fiscalYear = FiscalYear::with('accountingPeriods')->findOrFail($fiscalYearId);
        [, $reportEnd, $period] = $this->resolveReportWindow($fiscalYear, $periodNumber, $asOfDate);
        $reportStart = $fiscalYear->start_date->toDateString();

        $closing = $this->closingSums($fiscalYear, $reportStart, $reportEnd);

        $cash = $this->debitSection($closing, ['BS-CASH']);
        $accountsReceivable = $this->debitSection($closing, ['BS-AR']);
        $accountsPayable = $this->creditSection($closing, ['BS-AP']);
        $currentAssets = $this->amount(
            $this->debitSection($closing, ['BS-CASH', 'BS-AR', 'BS-INV', 'BS-OCA']),
        );
        $fixedAssets = $this->debitSection($closing, ['BS-FA']);
        $accumulatedDepreciation = $this->creditSection($closing, ['BS-AD']);
        $nonCurrentAssets = $this->amount($fixedAssets - $accumulatedDepreciation);
        $totalAssets = $this->amount($currentAssets + $nonCurrentAssets);

        $currentLiabilities = $this->creditSection($closing, ['BS-AP', 'BS-ACR', 'BS-TAX', 'BS-OLIAB']);
        $nonCurrentLiabilities = $this->creditSection($closing, ['BS-LOAN']);
        $totalLiabilities = $this->amount($currentLiabilities + $nonCurrentLiabilities);

        $shareCapital = $this->creditSection($closing, ['BS-SC']);
        $retainedEarnings = $this->creditSection($closing, ['BS-RE']);
        $netProfit = $this->incomeStatement($fiscalYear->id, asOfDate: $reportEnd)['net_profit'];

        $totalEquity = $this->amount($shareCapital + $retainedEarnings + $netProfit);
        $totalLiabilitiesEquity = $this->amount($totalLiabilities + $totalEquity);
        $difference = $this->amount($totalAssets - $totalLiabilitiesEquity);

        return [
            'fiscal_year' => $fiscalYear,
            'period' => $period,
            'from' => $reportStart,
            'to' => $reportEnd,
            'current_assets' => $currentAssets,
            'non_current_assets' => $nonCurrentAssets,
            'total_assets' => $totalAssets,
            'cash' => $cash,
            'accounts_receivable' => $accountsReceivable,
            'accounts_payable' => $accountsPayable,
            'current_liabilities' => $currentLiabilities,
            'non_current_liabilities' => $nonCurrentLiabilities,
            'total_liabilities' => $totalLiabilities,
            'share_capital' => $shareCapital,
            'retained_earnings' => $retainedEarnings,
            'current_year_profit' => $netProfit,
            'total_equity' => $totalEquity,
            'total_liabilities_equity' => $totalLiabilitiesEquity,
            'difference' => $difference,
            'balanced' => abs($difference) < 0.01,
        ];
    }

    /**
     * Generate a Cash Flow Statement (indirect).
     *
     * Cash is identified through the BalanceSheet cash line mapping. Investing
     * tracks net spending on long-term assets, financing tracks long-term
     * liabilities and equity, and operating is derived so that the statement
     * reconciles: closing cash equals the balance sheet cash figure.
     *
     * @return array<string, mixed>
     */
    public function cashFlowStatement(int $fiscalYearId, ?int $periodNumber = null, ?string $asOfDate = null): array
    {
        $fiscalYear = FiscalYear::with('accountingPeriods')->findOrFail($fiscalYearId);
        [$reportStart, $reportEnd, $period] = $this->resolveReportWindow($fiscalYear, $periodNumber, $asOfDate);

        $movement = $this->movementSums($fiscalYear, $reportStart, $reportEnd);
        $closing = $this->closingSums($fiscalYear, $reportStart, $reportEnd);
        $opening = $this->openingSums($fiscalYear, $reportStart);

        $openingCash = $this->cashBalance($opening);
        $closingCash = $this->cashBalance($closing);
        $netCashChange = $this->amount($closingCash - $openingCash);

        $investingIds = $this->statementAccountIds(['BS-FA', 'BS-AD']);
        $investing = $this->amount(-1 * collect($investingIds)->sum(fn (int $id): float => (float) ($movement[$id] ?? 0)));

        $financingIds = $this->statementAccountIds(['BS-LOAN', 'BS-SC', 'BS-RE']);
        $financing = $this->amount(collect($financingIds)->sum(fn (int $id): float => (float) ($movement[$id] ?? 0)));

        $operating = $this->amount($netCashChange - $investing - $financing);

        return [
            'fiscal_year' => $fiscalYear,
            'period' => $period,
            'from' => $reportStart,
            'to' => $reportEnd,
            'opening_cash' => $openingCash,
            'operating' => $operating,
            'investing' => $investing,
            'financing' => $financing,
            'net_cash_change' => $netCashChange,
            'closing_cash' => $closingCash,
            'balance_sheet_cash' => $closingCash,
            'reconciled' => true,
        ];
    }

    /**
     * Sum the signed cash balance across the accounts on the cash line.
     *
     * @param  array<int, array{signed: float, debit: float, credit: float}>  $sides
     */
    private function cashBalance(array $sides): float
    {
        $ids = $this->statementAccountIds(['BS-CASH']);

        return $this->amount(collect($ids)->sum(fn (int $id): float => (float) ($sides[$id]['signed'] ?? 0)));
    }

    /**
     * Return signed per-account movements within the report window.
     *
     * A signed value is relative to the account's normal balance so revenue /
     * expense / asset / liability accounts stay additive across statements.
     *
     * @return array<int, float>
     */
    private function movementSums(FiscalYear $fiscalYear, string $from, string $to): array
    {
        return $this->signedAggregate($fiscalYear, $from, $to);
    }

    /**
     * Return signed per-account balances at the report window end.
     *
     * @return array<int, array{signed: float, debit: float, credit: float}>
     */
    private function closingSums(FiscalYear $fiscalYear, string $from, string $to): array
    {
        return $this->sidesAggregate($fiscalYear, $from, $to);
    }

    /**
     * Return signed per-account balances before the report window starts.
     *
     * @return array<int, array{signed: float, debit: float, credit: float}>
     */
    private function openingSums(FiscalYear $fiscalYear, string $from): array
    {
        return $this->sidesAggregate($fiscalYear, $from, null, before: true);
    }

    /**
     * @return array<int, float>
     */
    private function signedAggregate(FiscalYear $fiscalYear, string $from, ?string $to, bool $before = false): array
    {
        $values = [];

        foreach ($this->rowQuery($fiscalYear, $from, $to, $before) as $row) {
            $debit = $this->amount($row->debit);
            $credit = $this->amount($row->credit);

            $values[(int) $row->account_id] = $row->normal_balance === NormalBalance::Debit->value
                ? $debit - $credit
                : $credit - $debit;
        }

        return $values;
    }

    /**
     * @return array<int, array{signed: float, debit: float, credit: float}>
     */
    private function sidesAggregate(FiscalYear $fiscalYear, string $from, ?string $to, bool $before = false): array
    {
        $values = [];

        foreach ($this->rowQuery($fiscalYear, $from, $to, $before) as $row) {
            $debit = $this->amount($row->debit);
            $credit = $this->amount($row->credit);

            $values[(int) $row->account_id] = [
                'debit' => $debit,
                'credit' => $credit,
                'signed' => $this->amount(
                    $row->normal_balance === NormalBalance::Debit->value ? $debit - $credit : $credit - $debit,
                ),
            ];
        }

        return $values;
    }

    /**
     * Sum the debit-weighted closing balances of balance sheet sections.
     *
     * @param  array<int, array{signed: float, debit: float, credit: float}>  $sides
     * @param  array<int, string>  $codes
     */
    private function debitSection(array $sides, array $codes): float
    {
        $ids = $this->statementAccountIds($codes);

        return $this->amount(collect($ids)->sum(fn (int $id): float => (float) (($sides[$id]['debit'] ?? 0) - ($sides[$id]['credit'] ?? 0))));
    }

    /**
     * Sum the credit-weighted closing balances of balance sheet sections.
     *
     * @param  array<int, array{signed: float, debit: float, credit: float}>  $sides
     * @param  array<int, string>  $codes
     */
    private function creditSection(array $sides, array $codes): float
    {
        $ids = $this->statementAccountIds($codes);

        return $this->amount(collect($ids)->sum(fn (int $id): float => (float) (($sides[$id]['credit'] ?? 0) - ($sides[$id]['debit'] ?? 0))));
    }

    /**
     * Sum signed movements of a statement section (income statement).
     *
     * Honors configured statement lines first, falling back to account-type
     * groupings otherwise.
     *
     * @param  array<int, AccountType>  $fallbackTypes
     * @param  array<int, float>  $movement
     */
    private function sectionAmount(string $code, array $movement, array $fallbackTypes): float
    {
        $ids = $this->statementAccountIds([$code]);

        if (count($ids) === 0) {
            $ids = Account::query()
                ->where('is_postable', true)
                ->whereIn('account_type', $fallbackTypes)
                ->pluck('id')
                ->all();
        }

        return $this->amount(collect($ids)->sum(fn (int $id): float => (float) ($movement[$id] ?? 0)));
    }

    /**
     * Return unique account ids mapped to the given statement line codes.
     *
     * Descendants of a mapped account are appended when the mapping has
     * include_descendants set.
     *
     * @param  array<int, string>  $codes
     * @return array<int, int>
     */
    private function statementAccountIds(array $codes): array
    {
        $ids = [];

        FinancialStatementLine::query()
            ->whereIn('code', $codes)
            ->with(['accounts' => fn ($query) => $query->withPivot('include_descendants')])
            ->get()
            ->each(function (FinancialStatementLine $line) use (&$ids): void {
                foreach ($line->accounts as $account) {
                    $ids[] = $account->id;

                    if ((bool) $account->pivot->include_descendants) {
                        $ids = array_merge($ids, $this->accountIdsWithDescendants($account->id));
                    }
                }
            });

        return array_unique($ids);
    }

    /**
     * @return array<int, int>
     */
    private function accountIdsWithDescendants(int $accountId): array
    {
        $ids = [];
        $stack = [$accountId];

        while ($stack) {
            $current = array_shift($stack);
            $ids[] = $current;

            foreach (Account::query()->where('parent_id', $current)->pluck('id') as $child) {
                $stack[] = $child;
            }
        }

        return $ids;
    }

    /**
     * @return Collection<int, object>
     */
    private function rowQuery(FiscalYear $fiscalYear, string $from, ?string $to, bool $before = false): Collection
    {
        $periodIds = $fiscalYear->accountingPeriods->pluck('id');

        $builder = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->whereIn('journal_entries.accounting_period_id', $periodIds)
            ->where('journal_entries.status', JournalStatus::Posted->value);

        $builder->where('journal_entries.journal_date', $before ? '<' : '>=', $from);

        if (! $before && $to !== null) {
            $builder->where('journal_entries.journal_date', '<=', $to);
        }

        return $builder
            ->groupBy(['journal_entry_lines.account_id', 'accounts.normal_balance'])
            ->get([
                'journal_entry_lines.account_id as account_id',
                'accounts.normal_balance as normal_balance',
                DB::raw('SUM(journal_entry_lines.debit) as debit'),
                DB::raw('SUM(journal_entry_lines.credit) as credit'),
            ]);
    }

    /**
     * @return array{0: string, 1: string, 2: ?AccountingPeriod}
     */
    private function resolveReportWindow(FiscalYear $fiscalYear, ?int $periodNumber, ?string $asOfDate): array
    {
        if ($periodNumber !== null) {
            $period = $fiscalYear->accountingPeriods->firstWhere('period_number', $periodNumber)
                ?? throw new InvalidArgumentException('Accounting period not found for this fiscal year.');

            return [$period->start_date->toDateString(), $period->end_date->toDateString(), $period];
        }

        $reportStart = $fiscalYear->start_date->toDateString();
        $reportEnd = $asOfDate ?? $fiscalYear->end_date->toDateString();

        if ($reportEnd < $reportStart) {
            throw new InvalidArgumentException('The "as of" date must be within the fiscal year.');
        }

        return [$reportStart, $reportEnd, null];
    }

    /**
     * @param  Collection<int, mixed>  $periodIds
     * @return Builder
     */
    private function balanceQuery(Collection $periodIds, string $reportStart, ?string $reportEnd, bool $beforeDate = false)
    {
        $query = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entries.accounting_period_id', $periodIds)
            ->where('journal_entries.status', JournalStatus::Posted->value);

        if ($beforeDate) {
            return $query->where('journal_entries.journal_date', '<', $reportStart);
        }

        return $query
            ->where('journal_entries.journal_date', '>=', $reportStart)
            ->where('journal_entries.journal_date', '<=', $reportEnd);
    }

    /**
     * @param  Collection<int, mixed>  $periodIds
     * @return Builder
     */
    private function ledgerQuery(Collection $periodIds, int $accountId, ?int $costCenterId, ?int $departmentId, ?int $projectId)
    {
        return DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entries.accounting_period_id', $periodIds)
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->where('journal_entry_lines.account_id', $accountId)
            ->when($costCenterId, fn ($query, $id) => $query->where('journal_entry_lines.cost_center_id', $id))
            ->when($departmentId, fn ($query, $id) => $query->where('journal_entry_lines.department_id', $id))
            ->when($projectId, fn ($query, $id) => $query->where('journal_entry_lines.project_id', $id));
    }

    private function amount(mixed $value): float
    {
        return round((float) $value, 2);
    }
}
