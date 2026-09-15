<?php

use App\Enums\AccountType;
use App\Enums\JournalStatus;
use App\Models\Account;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\OpeningBalance;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\PeriodService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'year' => 2026,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    app(PeriodService::class)->generateForFiscalYear($this->fiscalYear);

    $this->period = $this->fiscalYear->accountingPeriods()->where('period_number', 1)->firstOrFail();

    app()->instance('test_company', $this->company);
    app()->instance('test_period', $this->period);
});

function journalLineData(int $accountId, float $debit = 0, float $credit = 0): array
{
    return ['account_id' => $accountId, 'debit' => $debit, 'credit' => $credit];
}

function createBalancedJournal(array $lines, string $date = '2026-01-15'): JournalEntry
{
    return app(AccountingService::class)->createJournal([
        'company_id' => app('test_company')->id,
        'accounting_period_id' => app('test_period')->id,
        'journal_date' => $date,
        'description' => 'Test journal',
        'lines' => $lines,
    ]);
}

it('rejects an unbalanced journal entry', function () {
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();

    expect(fn () => createBalancedJournal([
        journalLineData($cash->id, debit: 100),
        journalLineData($revenue->id, credit: 50),
    ]))->toThrow(InvalidArgumentException::class, 'not balanced');
});

it('rejects a journal line with both debit and credit amounts', function () {
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();

    expect(fn () => createBalancedJournal([
        ['account_id' => $cash->id, 'debit' => 100, 'credit' => 100],
    ]))->toThrow(InvalidArgumentException::class);
});

it('walks a balanced journal through the full workflow', function () {
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();

    $entry = createBalancedJournal([
        journalLineData($cash->id, debit: 1000),
        journalLineData($revenue->id, credit: 1000),
    ]);

    expect($entry->status)->toBe(JournalStatus::Draft)
        ->and($entry->journal_number)->toStartWith('JV-');

    $entry = app(AccountingService::class)->submit($entry);
    expect($entry->status)->toBe(JournalStatus::Submitted);

    $entry = app(AccountingService::class)->approve($entry);
    expect($entry->status)->toBe(JournalStatus::Approved);

    $entry = app(AccountingService::class)->post($entry);
    expect($entry->status)->toBe(JournalStatus::Posted)
        ->and($entry->posted_at)->not->toBeNull()
        ->and($entry->lines()->count())->toBe(2);
});

it('rejects posting to a group account', function () {
    $group = Account::factory()->group()->withCode('1000')->create();
    $bank = Account::factory()->ofType(AccountType::Asset)->withCode('1112')->create();

    expect(fn () => createBalancedJournal([
        journalLineData($group->id, debit: 100),
        journalLineData($bank->id, credit: 100),
    ]))->toThrow(InvalidArgumentException::class, 'group account');
});

it('refuses to close a period that still contains unposted journals', function () {
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();

    createBalancedJournal([
        journalLineData($cash->id, debit: 100),
        journalLineData($revenue->id, credit: 100),
    ]);

    expect(fn () => app(PeriodService::class)->close($this->period))
        ->toThrow(InvalidArgumentException::class, 'unposted');
});

it('rejects posting into a closed period', function () {
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();

    $entry = createBalancedJournal([
        journalLineData($cash->id, debit: 100),
        journalLineData($revenue->id, credit: 100),
    ]);

    $entry = app(AccountingService::class)->submit($entry);
    $entry = app(AccountingService::class)->approve($entry);
    app(AccountingService::class)->post($entry);

    app(PeriodService::class)->close($this->period);

    expect(fn () => createBalancedJournal([
        journalLineData($cash->id, debit: 50),
        journalLineData($revenue->id, credit: 50),
    ]))->toThrow(InvalidArgumentException::class, 'closed accounting period');
});

it('creates an exact reversal of a posted journal', function () {
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();

    $entry = createBalancedJournal([
        journalLineData($cash->id, debit: 2500),
        journalLineData($revenue->id, credit: 2500),
    ]);

    $entry = app(AccountingService::class)->submit($entry);
    $entry = app(AccountingService::class)->approve($entry);
    $entry = app(AccountingService::class)->post($entry);

    $reversal = app(AccountingService::class)->reverse($entry);

    expect($reversal->status)->toBe(JournalStatus::Posted)
        ->and($entry->fresh()->is_reversed)->toBeTrue()
        ->and($reversal->journal_number)->not->toBe($entry->journal_number);

    $originalLine = $entry->lines()->first();
    $reversalLine = $reversal->lines()->first();

    expect((float) $reversalLine->debit)->toBe((float) $originalLine->credit)
        ->and((float) $reversalLine->credit)->toBe((float) $originalLine->debit);
});

it('prevents modification and deletion of posted journals', function () {
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();

    $entry = createBalancedJournal([
        journalLineData($cash->id, debit: 100),
        journalLineData($revenue->id, credit: 100),
    ]);

    $entry = app(AccountingService::class)->submit($entry);
    $entry = app(AccountingService::class)->approve($entry);
    $entry = app(AccountingService::class)->post($entry);

    expect(fn () => $entry->update(['description' => 'tampered']))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);
});

it('creates a balanced opening balance journal from recorded entries', function () {
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $capital = Account::factory()->ofType(AccountType::Equity)->withCode('3100')->create();

    OpeningBalance::factory()->debit('5000000')->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'account_id' => $cash->id,
    ]);

    OpeningBalance::factory()->credit('5000000')->create([
        'fiscal_year_id' => $this->fiscalYear->id,
        'account_id' => $capital->id,
    ]);

    $journal = app(AccountingService::class)->createOpeningBalance($this->fiscalYear);

    expect($journal->status)->toBe(JournalStatus::Posted)
        ->and($journal->lines()->count())->toBe(2)
        ->and($journal->journal_number)->toStartWith('JV-');
});

it('enforces unique account codes per company', function () {
    Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create(['company_id' => $this->company->id]);

    expect(fn () => Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create(['company_id' => $this->company->id]))
        ->toThrow(QueryException::class);
});
