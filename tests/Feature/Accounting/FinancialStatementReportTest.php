<?php

use App\Enums\AccountType;
use App\Enums\StatementType;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialStatementLine;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\FinancialStatementService;
use App\Services\Accounting\PeriodService;
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

    $this->cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $this->revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();
    $this->expense = Account::factory()->ofType(AccountType::Expense)->withCode('6100')->create();

    app()->instance('test_company', $this->company);
    app()->instance('test_fiscal_year', $this->fiscalYear);
});

function postJournal(array $lines, string $date, int $periodNumber = 1): JournalEntry
{
    $service = app(AccountingService::class);
    $period = app('test_fiscal_year')->accountingPeriods()->where('period_number', $periodNumber)->firstOrFail();

    $journal = $service->createJournal([
        'company_id' => app('test_company')->id,
        'accounting_period_id' => $period->id,
        'journal_date' => $date,
        'description' => 'Test journal '.$date,
        'lines' => $lines,
    ]);

    $service->submit($journal);
    $service->approve($journal);

    return $service->post($journal);
}

it('produces a trial balance whose total debit equals total credit', function () {
    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $this->revenue->id, 'credit' => 1000000],
    ], '2026-01-15');

    $report = app(FinancialStatementService::class)->trialBalance($this->fiscalYear->id);

    expect($report['totals']['closing_debit'])->toBe(1000000.0);
    expect($report['totals']['closing_credit'])->toBe(1000000.0);
    expect($report['totals']['closing_debit'])->toBe($report['totals']['closing_credit']);
});

it('restricts trial balance movement to the selected period', function () {
    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $this->revenue->id, 'credit' => 1000000],
    ], '2026-01-15', periodNumber: 1);

    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 500000],
        ['account_id' => $this->revenue->id, 'credit' => 500000],
    ], '2026-02-15', periodNumber: 2);

    $january = app(FinancialStatementService::class)->trialBalance($this->fiscalYear->id, periodNumber: 1);
    $cashRow = collect($january['rows'])->firstWhere('account_code', '1111');

    expect($cashRow['movement_debit'])->toBe(1000000.0);
    expect($cashRow['closing_debit'])->toBe(1000000.0);

    $february = app(FinancialStatementService::class)->trialBalance($this->fiscalYear->id, periodNumber: 2);
    $cashRow = collect($february['rows'])->firstWhere('account_code', '1111');

    expect($cashRow['opening_debit'])->toBe(1000000.0);
    expect($cashRow['movement_debit'])->toBe(500000.0);
    expect($cashRow['closing_debit'])->toBe(1500000.0);
});

it('only includes posted journals in the trial balance', function () {
    $service = app(AccountingService::class);
    $period = $this->fiscalYear->accountingPeriods()->where('period_number', 1)->firstOrFail();

    $service->createJournal([
        'company_id' => $this->company->id,
        'accounting_period_id' => $period->id,
        'journal_date' => '2026-01-10',
        'description' => 'Draft only',
        'lines' => [
            ['account_id' => $this->cash->id, 'debit' => 999999],
            ['account_id' => $this->revenue->id, 'credit' => 999999],
        ],
    ]);

    $report = app(FinancialStatementService::class)->trialBalance($this->fiscalYear->id);

    expect($report['totals']['closing_debit'])->toBe(0.0);
    expect($report['totals']['closing_credit'])->toBe(0.0);
});

it('computes a running balance for a debit-normal account', function () {
    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $this->revenue->id, 'credit' => 1000000],
    ], '2026-01-10');

    postJournal([
        ['account_id' => $this->cash->id, 'credit' => 300000],
        ['account_id' => $this->expense->id, 'debit' => 300000],
    ], '2026-02-10');

    $ledger = app(FinancialStatementService::class)->generalLedger(
        accountId: $this->cash->id,
        fiscalYearId: $this->fiscalYear->id,
        from: '2026-01-01',
        to: '2026-12-31',
    );

    expect($ledger['opening_balance'])->toBe(0.0);
    expect($ledger['rows'][0]['balance'])->toBe(1000000.0);
    expect($ledger['rows'][1]['balance'])->toBe(700000.0);
    expect($ledger['closing_balance'])->toBe(700000.0);
});

it('respects the normal balance when computing the running balance', function () {
    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 2000000],
        ['account_id' => $this->revenue->id, 'credit' => 2000000],
    ], '2026-01-10');

    $ledger = app(FinancialStatementService::class)->generalLedger(
        accountId: $this->revenue->id,
        fiscalYearId: $this->fiscalYear->id,
        from: '2026-01-01',
        to: '2026-12-31',
    );

    expect($ledger['rows'][0]['balance'])->toBe(2000000.0);
    expect($ledger['closing_balance'])->toBe(2000000.0);
});

it('includes an opening balance for postings dated before the ledger range', function () {
    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 500000],
        ['account_id' => $this->revenue->id, 'credit' => 500000],
    ], '2026-01-10');

    $ledger = app(FinancialStatementService::class)->generalLedger(
        accountId: $this->cash->id,
        fiscalYearId: $this->fiscalYear->id,
        from: '2026-02-01',
        to: '2026-12-31',
    );

    expect($ledger['opening_balance'])->toBe(500000.0);
    expect($ledger['rows'])->toBeEmpty();
    expect($ledger['closing_balance'])->toBe(500000.0);
});

/**
 * Attach a financial statement line mapping for one or more accounts.
 *
 * @param  array<int, Account>  $accounts
 */
function mapStatementLine(string $code, array $accounts, bool $includeDescendants = false): FinancialStatementLine
{
    $line = FinancialStatementLine::factory()->create([
        'statement_type' => StatementType::IncomeStatement,
        'code' => $code,
    ]);

    $line->accounts()->syncWithPivotValues(
        collect($accounts)->pluck('id')->all(),
        ['include_descendants' => $includeDescendants],
    );

    return $line;
}

it('computes an income statement from account-type fallbacks', function () {
    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $this->revenue->id, 'credit' => 1000000],
    ], '2026-01-15');

    postJournal([
        ['account_id' => $this->expense->id, 'debit' => 250000],
        ['account_id' => $this->cash->id, 'credit' => 250000],
    ], '2026-02-10');

    $statement = app(FinancialStatementService::class)->incomeStatement($this->fiscalYear->id);

    expect($statement['revenue'])->toBe(1000000.0);
    expect($statement['cost_of_sales'])->toBe(0.0);
    expect($statement['gross_profit'])->toBe(1000000.0);
    expect($statement['operating_expenses'])->toBe(250000.0);
    expect($statement['operating_profit'])->toBe(750000.0);
    expect($statement['net_profit'])->toBe(750000.0);
});

it('honors statement line mappings for the income statement', function () {
    mapStatementLine('IS-REV', [$this->revenue]);
    mapStatementLine('IS-OPEX', [$this->expense]);

    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $this->revenue->id, 'credit' => 1000000],
    ], '2026-01-15');

    $statement = app(FinancialStatementService::class)->incomeStatement($this->fiscalYear->id);

    expect($statement['revenue'])->toBe(1000000.0);
    expect($statement['operating_expenses'])->toBe(0.0);
});

it('produces a balanced balance sheet with equity closed by net profit', function () {
    $equityShareCapital = Account::factory()->ofType(AccountType::Equity)->withCode('3100')->create();
    mapStatementLine('BS-CASH', [$this->cash]);
    mapStatementLine('BS-SC', [$equityShareCapital]);

    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $this->revenue->id, 'credit' => 1000000],
    ], '2026-01-15');

    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $equityShareCapital->id, 'credit' => 1000000],
    ], '2026-01-20');

    $statement = app(FinancialStatementService::class)->balanceSheet($this->fiscalYear->id);

    expect($statement['current_assets'])->toBe(2000000.0);
    expect($statement['total_assets'])->toBe(2000000.0);
    expect($statement['share_capital'])->toBe(1000000.0);
    expect($statement['current_year_profit'])->toBe(1000000.0);
    expect($statement['total_equity'])->toBe(2000000.0);
    expect($statement['total_liabilities_equity'])->toBe(2000000.0);
    expect($statement['difference'])->toBe(0.0);
    expect($statement['balanced'])->toBeTrue();
});

it('reconciles the cash flow statement with the balance sheet cash', function () {
    $equityShareCapital = Account::factory()->ofType(AccountType::Equity)->withCode('3100')->create();
    mapStatementLine('BS-CASH', [$this->cash]);
    mapStatementLine('BS-SC', [$equityShareCapital]);

    postJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $equityShareCapital->id, 'credit' => 1000000],
    ], '2026-01-20');

    postJournal([
        ['account_id' => $this->expense->id, 'debit' => 250000],
        ['account_id' => $this->cash->id, 'credit' => 250000],
    ], '2026-02-10');

    $statement = app(FinancialStatementService::class)->cashFlowStatement($this->fiscalYear->id);

    expect($statement['opening_cash'])->toBe(0.0);
    expect($statement['financing'])->toBe(1000000.0);
    expect($statement['investing'])->toBe(0.0);
    expect($statement['operating'])->toBe(-250000.0);
    expect($statement['net_cash_change'])->toBe(750000.0);
    expect($statement['closing_cash'])->toBe(750000.0);
    expect($statement['balance_sheet_cash'])->toBe($statement['closing_cash']);
    expect($statement['reconciled'])->toBeTrue();
});
