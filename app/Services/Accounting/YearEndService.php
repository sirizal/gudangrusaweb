<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\FiscalYearStatus;
use App\Enums\JournalStatus;
use App\Enums\NormalBalance;
use App\Models\Account;
use App\Models\FinancialStatementLine;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\OpeningBalance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class YearEndService
{
    public function __construct(
        private readonly JournalNumberGenerator $numbers,
    ) {}

    /**
     * Close a fiscal year: post the year-end closing journal that moves the
     * year's net result out of the P&L accounts into the current-year-profit
     * equity account, create the next fiscal year with its periods, and carry
     * forward balanced opening balances for the next year (which the user then
     * posts explicitly via the opening balance flow).
     *
     * @return array{
     *     closing_journal: JournalEntry,
     *     net_profit: float,
     *     next_fiscal_year: FiscalYear,
     *     opening_balances_created: int,
     * }
     */
    public function closeFiscalYear(FiscalYear $fiscalYear, ?User $actor = null): array
    {
        DB::transaction(function () use ($fiscalYear, $actor, &$result): void {
            if ($fiscalYear->status !== FiscalYearStatus::Open) {
                throw new InvalidArgumentException('Only an open fiscal year can be closed.');
            }

            $periods = $fiscalYear->accountingPeriods()->orderBy('period_number')->get();

            if ($periods->count() !== 12) {
                throw new InvalidArgumentException('The fiscal year must have all 12 accounting periods before it can be closed.');
            }

            $openJournals = $fiscalYear->accountingPeriods()
                ->withCount(['journalEntries as unposted' => fn ($query) => $query
                    ->whereNotIn('status', [JournalStatus::Posted->value, JournalStatus::Reversed->value, JournalStatus::Cancelled->value])])
                ->get()
                ->sum('unposted');

            if ($openJournals > 0) {
                throw new InvalidArgumentException('Cannot close the fiscal year while it still contains unposted journals.');
            }

            if (JournalEntry::query()
                ->where('source', 'year_end')
                ->whereIn('accounting_period_id', $periods->pluck('id'))
                ->exists()) {
                throw new InvalidArgumentException('A year-end closing journal already exists for this fiscal year.');
            }

            $equityAccount = $this->resolveClosingEquityAccount($fiscalYear);
            $movement = $this->pnlMovement($fiscalYear);
            $closingJournal = $this->postClosingJournal($fiscalYear, $periods->last(), $equityAccount, $movement, $actor);

            $netProfit = $movement['net_profit'];
            $fiscalYear->update(['status' => FiscalYearStatus::Closed]);
            $fiscalYear->recordAudit('fiscal_year_closed', ['closing_journal' => $closingJournal->journal_number]);

            $next = $this->createNextFiscalYear($fiscalYear);
            $carried = $this->carryForwardOpeningBalances($fiscalYear, $next);

            $result = [
                'closing_journal' => $closingJournal->load('lines'),
                'net_profit' => $netProfit,
                'next_fiscal_year' => $next,
                'opening_balances_created' => $carried,
            ];
        });

        return $result;
    }

    /**
     * Resolve the equity account the year-end result closes into. The BS-CYPL
     * (current year profit/loss) statement line wins; BS-RE (retained
     * earnings) is the fallback. Resolved through the configurable financial
     * statement mapping rather than a hard-coded account code.
     */
    private function resolveClosingEquityAccount(FiscalYear $fiscalYear): Account
    {
        foreach (['BS-CYPL', 'BS-RE'] as $code) {
            $line = FinancialStatementLine::query()
                ->where('code', $code)
                ->with('accounts')
                ->first();

            if ($line === null) {
                continue;
            }

            foreach ($line->accounts as $account) {
                if ($account->is_postable && $account->is_active) {
                    return $account;
                }
            }
        }

        throw new InvalidArgumentException('No closing equity account is configured. Map an account to the BS-CYPL or BS-RE financial statement line.');
    }

    /**
     * Return the signed P&L movement for the fiscal year.
     *
     * @return array{signed: array<int, float>, net_profit: float}
     */
    private function pnlMovement(FiscalYear $fiscalYear): array
    {
        $types = [
            AccountType::Revenue,
            AccountType::CostOfSales,
            AccountType::Expense,
            AccountType::OtherIncome,
            AccountType::OtherExpense,
        ];

        $rows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->whereIn('journal_entries.accounting_period_id', $fiscalYear->accountingPeriods()->pluck('id'))
            ->where('journal_entries.status', JournalStatus::Posted->value)
            ->whereIn('accounts.account_type', array_map(fn (AccountType $type): string => $type->value, $types))
            ->where('accounts.is_postable', true)
            ->groupBy(['journal_entry_lines.account_id', 'accounts.normal_balance'])
            ->get([
                'journal_entry_lines.account_id as account_id',
                'accounts.normal_balance as normal_balance',
                DB::raw('SUM(journal_entry_lines.debit) as debit'),
                DB::raw('SUM(journal_entry_lines.credit) as credit'),
            ]);

        $signed = [];
        $netProfit = 0.0;

        foreach ($rows as $row) {
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            $balance = $row->normal_balance === NormalBalance::Debit->value ? $debit - $credit : $credit - $debit;

            $signed[(int) $row->account_id] = round($balance, 2);

            $sign = $row->normal_balance === NormalBalance::Debit->value ? -1 : 1;
            $netProfit += $sign * $balance;
        }

        return [
            'signed' => $signed,
            'net_profit' => round($netProfit, 2),
        ];
    }

    /**
     * Post the closing journal. Every P&L account is brought back to zero and
     * the net result lands on the closing equity account. The journal is
     * created directly as Posted (not through the create/approve/post flow),
     * mirroring how opening balance journals are created.
     *
     * @param  array{signed: array<int, float>, net_profit: float}  $movement
     */
    private function postClosingJournal(FiscalYear $fiscalYear, $lastPeriod, Account $equityAccount, array $movement, ?User $actor): JournalEntry
    {
        $lines = [];

        foreach ($movement['signed'] as $accountId => $balance) {
            if (abs($balance) < 0.005) {
                continue;
            }

            $account = Account::find($accountId);

            if ($account->normal_balance === NormalBalance::Debit) {
                $lines[] = ['account_id' => $accountId, 'debit' => 0, 'credit' => abs($balance)];
            } else {
                $lines[] = ['account_id' => $accountId, 'debit' => abs($balance), 'credit' => 0];
            }
        }

        if ($movement['net_profit'] > 0) {
            $lines[] = ['account_id' => $equityAccount->id, 'debit' => 0, 'credit' => abs($movement['net_profit'])];
        } elseif ($movement['net_profit'] < 0) {
            $lines[] = ['account_id' => $equityAccount->id, 'debit' => abs($movement['net_profit']), 'credit' => 0];
        }

        if ($lines === []) {
            throw new InvalidArgumentException('There is no P&L activity to close for this fiscal year.');
        }

        $entry = JournalEntry::create([
            'company_id' => $fiscalYear->company_id,
            'journal_number' => $this->numbers->next($lastPeriod),
            'journal_date' => $fiscalYear->end_date->toDateString(),
            'accounting_period_id' => $lastPeriod->id,
            'reference_type' => FiscalYear::class,
            'reference_id' => $fiscalYear->id,
            'description' => 'Year-end closing '.$fiscalYear->name,
            'status' => JournalStatus::Posted,
            'source' => 'year_end',
            'posted_at' => now(),
            'posted_by' => $actor?->id ?? auth()->id(),
        ]);

        foreach ($lines as $line) {
            $entry->lines()->create($line);
        }

        $entry->recordAudit('year_end_closing_posted', ['journal_number' => $entry->journal_number]);

        return $entry;
    }

    /**
     * Create the fiscal year that follows the closing year and its periods.
     */
    private function createNextFiscalYear(FiscalYear $closed): FiscalYear
    {
        $next = FiscalYear::query()
            ->where('company_id', $closed->company_id)
            ->where('year', $closed->year + 1)
            ->first();

        if ($next === null) {
            $next = FiscalYear::create([
                'company_id' => $closed->company_id,
                'name' => 'FY '.($closed->year + 1),
                'year' => $closed->year + 1,
                'start_date' => $closed->end_date->copy()->addDay(),
                'end_date' => $closed->end_date->copy()->addDay()->endOfYear(),
                'status' => FiscalYearStatus::Open,
                'is_current' => true,
            ]);
        }

        $next->is_current = true;
        $next->save();

        app(PeriodService::class)->generateForFiscalYear($next);

        return $next;
    }

    /**
     * Carry forward balanced opening balances for the next fiscal year from
     * the closing year's asset, liability and equity net positions. Only
     * balance sheet accounts (asset/liability/equity) are carried; P&L has
     * already been zeroed into the current-year-profit equity account. The
     * caller posts the opening journal explicitly.
     */
    private function carryForwardOpeningBalances(FiscalYear $closed, FiscalYear $next): int
    {
        $rows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->whereIn('journal_entries.accounting_period_id', $closed->accountingPeriods()->pluck('id'))
            ->whereIn('journal_entries.status', [JournalStatus::Posted->value, JournalStatus::Reversed->value])
            ->whereIn('accounts.account_type', [
                AccountType::Asset->value,
                AccountType::Liability->value,
                AccountType::Equity->value,
            ])
            ->where('accounts.is_postable', true)
            ->groupBy(['journal_entry_lines.account_id', 'accounts.normal_balance'])
            ->get([
                'journal_entry_lines.account_id as account_id',
                'accounts.normal_balance as normal_balance',
                DB::raw('SUM(journal_entry_lines.debit) as debit'),
                DB::raw('SUM(journal_entry_lines.credit) as credit'),
            ]);

        $count = 0;

        foreach ($rows as $row) {
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            $signed = $row->normal_balance === NormalBalance::Debit->value
                ? $debit - $credit
                : $credit - $debit;

            if (abs($signed) < 0.005) {
                continue;
            }

            $isDebit = $row->normal_balance === NormalBalance::Debit->value;
            $amount = abs($signed);

            OpeningBalance::create([
                'fiscal_year_id' => $next->id,
                'account_id' => (int) $row->account_id,
                'debit' => $isDebit ? $amount : 0,
                'credit' => $isDebit ? 0 : $amount,
            ]);

            $count++;
        }

        return $count;
    }
}
