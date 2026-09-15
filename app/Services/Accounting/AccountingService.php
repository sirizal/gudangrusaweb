<?php

namespace App\Services\Accounting;

use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Enums\PeriodStatus;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\OpeningBalance;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AccountingService
{
    public function __construct(
        private readonly JournalNumberGenerator $numbers,
    ) {}

    /**
     * Create a draft journal entry from validated data.
     *
     * @param  array<string, mixed>  $data
     */
    public function createJournal(array $data, ?User $actor = null): JournalEntry
    {
        $period = AccountingPeriod::findOrFail($data['accounting_period_id']);
        $lines = $this->normalizeLines($data['lines'] ?? []);
        $this->assertLinesValid($lines, $period);

        return DB::transaction(function () use ($data, $period, $lines): JournalEntry {
            $entry = JournalEntry::create([
                'company_id' => $data['company_id'] ?? null,
                'journal_number' => $this->numbers->next($period),
                'journal_date' => $data['journal_date'],
                'accounting_period_id' => $period->id,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'] ?? null,
                'status' => JournalStatus::Draft,
                'source' => $data['source'] ?? 'manual',
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'department_id' => $line['department_id'] ?? null,
                    'project_id' => $line['project_id'] ?? null,
                    'description' => $line['description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'tax_code_id' => $line['tax_code_id'] ?? null,
                    'reference' => $line['reference'] ?? null,
                ]);
            }

            $entry->recordAudit('journal_created', ['journal_number' => $entry->journal_number]);

            return $entry->load('lines');
        });
    }

    public function submit(JournalEntry $entry): JournalEntry
    {
        $this->assertStatus($entry, JournalStatus::Draft);

        $entry->update(['status' => JournalStatus::Submitted]);
        $entry->recordAudit('journal_submitted', ['journal_number' => $entry->journal_number]);

        return $entry;
    }

    public function approve(JournalEntry $entry, ?User $actor = null): JournalEntry
    {
        $this->assertStatus($entry, JournalStatus::Submitted);

        $entry->update([
            'status' => JournalStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $actor?->id ?? auth()->id(),
        ]);
        $entry->recordAudit('journal_approved', ['journal_number' => $entry->journal_number]);

        return $entry;
    }

    public function cancel(JournalEntry $entry): JournalEntry
    {
        $this->assertStatus($entry, JournalStatus::Draft, JournalStatus::Submitted);

        $entry->update(['status' => JournalStatus::Cancelled]);
        $entry->recordAudit('journal_cancelled', ['journal_number' => $entry->journal_number]);

        return $entry;
    }

    public function post(JournalEntry $entry, ?User $actor = null): JournalEntry
    {
        $this->assertStatus($entry, JournalStatus::Approved);

        if ($entry->accountingPeriod->status !== PeriodStatus::Open) {
            throw new InvalidArgumentException('Cannot post a journal into a closed accounting period.');
        }

        $this->assertLinesValid($entry->lines->map(fn ($line): array => $line->toArray())->all(), $entry->accountingPeriod);

        $entry->update([
            'status' => JournalStatus::Posted,
            'posted_at' => now(),
            'posted_by' => $actor?->id ?? auth()->id(),
        ]);
        $entry->recordAudit('journal_posted', ['journal_number' => $entry->journal_number]);

        return $entry;
    }

    /**
     * Create an exact reversal of a posted journal and post it immediately.
     *
     * @param  array{journal_date?: string, description?: string}  $extra
     */
    public function reverse(JournalEntry $entry, ?User $actor = null, array $extra = []): JournalEntry
    {
        $this->assertStatus($entry, JournalStatus::Posted);

        return DB::transaction(function () use ($entry, $actor, $extra): JournalEntry {
            $period = $entry->accountingPeriod;

            if ($period->status !== PeriodStatus::Open) {
                throw new InvalidArgumentException('Cannot reverse a journal into a closed accounting period.');
            }

            $reversal = JournalEntry::create([
                'company_id' => $entry->company_id,
                'journal_number' => $this->numbers->next($period),
                'journal_date' => $extra['journal_date'] ?? now()->toDateString(),
                'accounting_period_id' => $period->id,
                'reference_type' => JournalEntry::class,
                'reference_id' => $entry->id,
                'description' => trim('Reversal of '.$entry->journal_number.(! empty($extra['description']) ? ' - '.$extra['description'] : '')),                'status' => JournalStatus::Posted,
                'source' => 'reversal',
                'reversed_journal_id' => $entry->id,
                'posted_at' => now(),
                'posted_by' => $actor?->id ?? auth()->id(),
            ]);

            foreach ($entry->lines as $line) {
                $reversal->lines()->create([
                    'account_id' => $line->account_id,
                    'cost_center_id' => $line->cost_center_id,
                    'department_id' => $line->department_id,
                    'project_id' => $line->project_id,
                    'description' => $line->description,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'tax_code_id' => $line->tax_code_id,
                    'reference' => $line->reference,
                ]);
            }

            DB::table('journal_entries')->where('id', $entry->getKey())->update(['is_reversed' => true]);
            $entry->is_reversed = true;
            $entry->recordAudit('journal_reversed', ['reversal_journal' => $reversal->journal_number]);

            return $reversal->load('lines');
        });
    }

    /**
     * Create and post the opening balance journal for a fiscal year from the
     * recorded opening balance entries.
     */
    public function createOpeningBalance(FiscalYear $fiscalYear, ?User $actor = null): JournalEntry
    {
        $entries = OpeningBalance::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->with('account')
            ->get();

        if ($entries->isEmpty()) {
            throw new InvalidArgumentException('No opening balance entries recorded for this fiscal year.');
        }

        foreach ($entries as $ob) {
            if (! in_array($ob->account->account_type, [AccountType::Asset, AccountType::Liability, AccountType::Equity], true)) {
                throw new InvalidArgumentException('Opening balances can only be recorded for asset, liability, or equity accounts.');
            }
        }

        $period = $fiscalYear->accountingPeriods()->where('period_number', 1)->first()
            ?? throw new InvalidArgumentException('The fiscal year does not have a January accounting period.');

        if ($period->status !== PeriodStatus::Open) {
            throw new InvalidArgumentException('Cannot post opening balance into a closed period.');
        }

        $lines = $entries
            ->map(fn (OpeningBalance $ob): array => [
                'account_id' => $ob->account_id,
                'debit' => $ob->debit,
                'credit' => $ob->credit,
                'description' => 'Opening balance '.$ob->account->account_code.' - '.$fiscalYear->name,
            ])
            ->all();

        $this->assertLinesValid($lines, $period);

        return DB::transaction(function () use ($fiscalYear, $period, $lines, $actor): JournalEntry {
            $entry = JournalEntry::create([
                'company_id' => $fiscalYear->company_id,
                'journal_number' => $this->numbers->next($period),
                'journal_date' => $fiscalYear->start_date,
                'accounting_period_id' => $period->id,
                'description' => 'Opening balance '.$fiscalYear->name,
                'status' => JournalStatus::Posted,
                'source' => 'opening_balance',
                'posted_at' => now(),
                'posted_by' => $actor?->id ?? auth()->id(),
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create($line);
            }

            $entry->recordAudit('opening_balance_posted', ['journal_number' => $entry->journal_number]);

            return $entry->load('lines');
        });
    }

    /**
     * Calculate the net activity for an account between two dates.
     * Returns an array with debit, credit and net amounts.
     *
     * @return array{debit: string, credit: string, net: string}
     */
    public function accountBalance(Account $account, CarbonInterface|string $from, CarbonInterface|string $to): array
    {
        $query = $account->journalEntryLines()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereBetween('journal_entries.journal_date', [$from, $to])
            ->whereIn('journal_entries.status', [JournalStatus::Posted->value, JournalStatus::Reversed->value]);

        $debit = (float) $query->sum('journal_entry_lines.debit');
        $credit = (float) $query->sum('journal_entry_lines.credit');

        $net = $account->normal_balance->isDebit() ? $debit - $credit : $credit - $debit;

        return [
            'debit' => number_format($debit, 2, '.', ''),
            'credit' => number_format($credit, 2, '.', ''),
            'net' => number_format($net, 2, '.', ''),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     */
    private function assertLinesValid(array $lines, AccountingPeriod $period): void
    {
        if ($period->status !== PeriodStatus::Open) {
            throw new InvalidArgumentException('Cannot post a journal into a closed accounting period.');
        }

        if ($lines === []) {
            throw new InvalidArgumentException('A journal must contain at least one line.');
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit < 0 || $credit < 0) {
                throw new InvalidArgumentException('Debit and credit amounts must not be negative.');
            }

            if ($debit > 0 && $credit > 0) {
                throw new InvalidArgumentException('A journal line cannot contain both a debit and a credit amount.');
            }

            if ($debit == 0 && $credit == 0) {
                throw new InvalidArgumentException('Each journal line must have a debit or a credit amount.');
            }

            $account = Account::find($line['account_id']);

            if (! $account || ! $account->is_active) {
                throw new InvalidArgumentException('Account does not exist or is inactive.');
            }

            if ($account->is_group) {
                throw new InvalidArgumentException('Cannot post to a group account: '.$account->account_code.' '.$account->account_name);
            }

            if (! $account->is_postable) {
                throw new InvalidArgumentException('Account is not postable: '.$account->account_code.' '.$account->account_name);
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if (abs($totalDebit - $totalCredit) > 0.004) {
            throw new InvalidArgumentException('Journal is not balanced. Total debit must equal total credit.');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array<string, mixed>>
     */
    private function normalizeLines(array $lines): array
    {
        return array_values(array_filter($lines, fn (array $line): bool => isset($line['account_id'])));
    }

    private function assertStatus(JournalEntry $entry, JournalStatus ...$expected): void
    {
        if (! in_array($entry->status, $expected, true)) {
            throw new InvalidArgumentException('Journal '.$entry->journal_number.' cannot be processed in its current status.');
        }
    }
}
