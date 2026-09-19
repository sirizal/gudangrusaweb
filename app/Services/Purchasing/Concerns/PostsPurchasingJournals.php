<?php

namespace App\Services\Purchasing\Concerns;

use App\Enums\JournalStatus;
use App\Enums\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\FinancialStatementLine;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

trait PostsPurchasingJournals
{
    /**
     * Resolve a postable account from a financial statement line mapping.
     * Group accounts are resolved to their first postable descendant via the
     * parent_id chain (account codes are not prefix-nested).
     */
    protected function resolveAccount(string $statementLineCode, int $companyId): Account
    {
        $line = FinancialStatementLine::query()
            ->where('code', $statementLineCode)
            ->with('accounts')
            ->first();

        foreach ($line?->accounts ?? [] as $account) {
            if ($account->is_postable && $account->is_active) {
                return $account;
            }

            if (! $account->is_group) {
                continue;
            }

            $descendant = $this->postableDescendant($account, $companyId);

            if ($descendant) {
                return $descendant;
            }
        }

        throw new InvalidArgumentException('No postable account is mapped to the '.$statementLineCode.' financial statement line.');
    }

    protected function postableDescendant(Account $group, int $companyId): ?Account
    {
        $accounts = Account::query()->where('company_id', $companyId)->get();
        $byId = $accounts->keyBy('id');

        return $accounts
            ->filter(function (Account $account) use ($group, $byId): bool {
                if (! $account->is_postable || ! $account->is_active) {
                    return false;
                }

                $current = $account;

                while ($current?->parent_id) {
                    $current = $byId->get($current->parent_id);

                    if ($current?->id === $group->id) {
                        return true;
                    }
                }

                return false;
            })
            ->sortBy('account_code')
            ->first();
    }

    protected function resolvePeriod(int $companyId, string|CarbonInterface $date): AccountingPeriod
    {
        $date = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

        $fiscalYear = FiscalYear::query()
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();

        if (! $fiscalYear) {
            throw new InvalidArgumentException('No fiscal year covers the transaction date '.$date->toDateString().'.');
        }

        $period = AccountingPeriod::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();

        if (! $period) {
            throw new InvalidArgumentException('No accounting period covers the transaction date '.$date->toDateString().'.');
        }

        if ($period->status !== PeriodStatus::Open) {
            throw new InvalidArgumentException('Cannot post into the closed accounting period '.$period->period_name.'.');
        }

        return $period;
    }

    /**
     * Create and post a balanced journal entry.
     *
     * @param  array<int, array{account_id: int, description: string, debit: float, credit: float}>  $lines
     * @param  array{date: string|CarbonInterface, description: string, source: string, reference_type: string, reference_id: int, company_id: int}  $meta
     */
    protected function postJournal(array $lines, array $meta, ?User $actor = null): JournalEntry
    {
        $period = $this->resolvePeriod($meta['company_id'], $meta['date']);
        $journalDate = $meta['date'] instanceof CarbonInterface ? $meta['date']->toDateString() : Carbon::parse($meta['date'])->toDateString();

        return DB::transaction(function () use ($lines, $meta, $period, $journalDate, $actor): JournalEntry {
            $journal = JournalEntry::create([
                'company_id' => $meta['company_id'],
                'journal_number' => $this->journalNumbers->next($period),
                'journal_date' => $journalDate,
                'accounting_period_id' => $period->id,
                'reference_type' => $meta['reference_type'],
                'reference_id' => $meta['reference_id'],
                'description' => $meta['description'],
                'status' => JournalStatus::Posted,
                'source' => $meta['source'],
                'posted_at' => now(),
                'posted_by' => $actor?->id ?? auth()->id(),
            ]);

            foreach ($lines as $line) {
                $journal->lines()->create([
                    'account_id' => $line['account_id'],
                    'description' => $line['description'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            return $journal;
        });
    }
}
