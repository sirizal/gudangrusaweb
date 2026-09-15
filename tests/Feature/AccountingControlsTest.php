<?php

use App\Enums\AccountType;
use App\Enums\FiscalYearStatus;
use App\Enums\JournalStatus;
use App\Enums\PeriodStatus;
use App\Enums\Role;
use App\Enums\StatementType;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\FinancialStatementLine;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Role as RoleModel;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\PeriodService;
use App\Services\Accounting\YearEndService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

function controlsCompanyAndYear(array $yearState = []): array
{
    $company = Company::factory()->create();
    $fiscalYear = FiscalYear::factory()->create([
        'company_id' => $company->id,
        'year' => 2026,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        ...$yearState,
    ]);

    app(PeriodService::class)->generateForFiscalYear($fiscalYear);

    return [$company, $fiscalYear];
}

function controlsMapStatementLine(string $code, StatementType $type, array $accounts): void
{
    $line = FinancialStatementLine::factory()->create([
        'statement_type' => $type,
        'code' => $code,
    ]);

    $line->accounts()->syncWithPivotValues(
        collect($accounts)->pluck('id')->all(),
        ['include_descendants' => false],
    );
}

function controlsPostApprovedJournal(AccountingService $service, FiscalYear $fiscalYear, int $accountingPeriodId, array $lines): JournalEntry
{
    $journal = $service->createJournal([
        'company_id' => $fiscalYear->company_id,
        'accounting_period_id' => $accountingPeriodId,
        'journal_date' => $fiscalYear->start_date->format('Y-m-d'),
        'description' => 'Controls test journal',
        'lines' => $lines,
    ]);

    $service->submit($journal);
    $service->approve($journal);

    return $service->post($journal);
}

it('records approval metadata and an audit trail when a journal is approved', function () {
    [$company, $fiscalYear] = controlsCompanyAndYear();
    $period = $fiscalYear->accountingPeriods()->where('period_number', 1)->firstOrFail();
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();

    $actor = User::factory()->create();
    actingAs($actor);
    $service = app(AccountingService::class);

    $journal = $service->createJournal([
        'company_id' => $company->id,
        'accounting_period_id' => $period->id,
        'journal_date' => '2026-01-10',
        'description' => 'Approval audit test',
        'lines' => [
            ['account_id' => $cash->id, 'debit' => 5000000],
            ['account_id' => $revenue->id, 'credit' => 5000000],
        ],
    ]);

    $service->submit($journal);
    $approved = $service->approve($journal, $actor);

    $approved->refresh();

    expect($approved->status)->toBe(JournalStatus::Approved);
    expect($approved->approved_at)->not->toBeNull();
    expect($approved->approved_by)->toBe($actor->id);

    $audits = $approved->audits()->get()->map(fn (AuditLog $log): string => $log->action)->all();

    expect($audits)->toContain('journal_created');
    expect($audits)->toContain('journal_approved');

    $audit = $approved->audits()->where('action', 'journal_approved')->first();

    expect($audit->user_id)->toBe($actor->id);
    expect($audit->changes)->toHaveKey('journal_number');
});

it('records a period closed and reopened audit trail', function () {
    [$company, $fiscalYear] = controlsCompanyAndYear();
    $period = $fiscalYear->accountingPeriods()->where('period_number', 1)->firstOrFail();

    app(PeriodService::class)->close($period);
    $period->refresh();

    expect($period->status)->toBe(PeriodStatus::Closed);

    $closedAudits = $period->audits()->get()->map(fn (AuditLog $log): string => $log->action)->all();
    expect($closedAudits)->toContain('period_closed');

    app(PeriodService::class)->reopen($period);
    $period->refresh();

    expect($period->status)->toBe(PeriodStatus::Open);

    $reopenAudits = $period->audits()->get()->map(fn (AuditLog $log): string => $log->action)->all();
    expect($reopenAudits)->toContain('period_reopened');
});

it('closes all accounting periods that contain only posted journals', function () {
    [$company, $fiscalYear] = controlsCompanyAndYear();
    $period = $fiscalYear->accountingPeriods()->where('period_number', 1)->firstOrFail();
    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();

    controlsPostApprovedJournal(
        app(AccountingService::class),
        $fiscalYear,
        $period->id,
        [
            ['account_id' => $cash->id, 'debit' => 100000],
            ['account_id' => $revenue->id, 'credit' => 100000],
        ],
    );

    $fiscalYear->accountingPeriods()->each(fn (AccountingPeriod $p) => app(PeriodService::class)->close($p));

    expect($fiscalYear->accountingPeriods()->where('status', 'open')->count())->toBe(0);

    expect(fn () => controlsPostApprovedJournal(
        app(AccountingService::class),
        $fiscalYear,
        $period->id,
        [
            ['account_id' => $cash->id, 'debit' => 1],
            ['account_id' => $revenue->id, 'credit' => 1],
        ],
    ))->toThrow(InvalidArgumentException::class);
});

it('only lets users with an accounting role access the accounting panel', function () {
    $this->seed(AccountingSeeder::class);

    $staff = User::factory()->create();
    $staff->roles()->attach(RoleModel::where('code', Role::Viewer->value)->first());
    actingAs($staff);

    get('/accounting')->assertOk();

    $outsider = User::factory()->create();
    actingAs($outsider);

    get('/accounting')->assertForbidden();
});

it('assigns roles through the users resource and requires roles to log in', function () {
    $this->seed(AccountingSeeder::class);

    $manager = User::factory()->create();
    $manager->roles()->attach(RoleModel::where('code', Role::FinanceManager->value)->first());
    actingAs($manager);

    get('/accounting/users')->assertOk();

    $newUser = User::factory()->create();
    $viewer = RoleModel::where('code', Role::Viewer->value)->first();

    $newUser->roles()->attach($viewer);

    expect($newUser->roles()->where('code', Role::Viewer->value)->exists())->toBeTrue();

    actingAs($newUser);
    get('/accounting')->assertOk();
});

it('performs a full fiscal year-end closing and carries forward opening balances', function () {
    [$company, $fiscalYear] = controlsCompanyAndYear();

    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();
    $expense = Account::factory()->ofType(AccountType::Expense)->withCode('6100')->create();
    $currentYearProfit = Account::factory()->ofType(AccountType::Equity)->withCode('3300')->create();

    controlsMapStatementLine('BS-CYPL', StatementType::BalanceSheet, [$currentYearProfit]);

    $service = app(AccountingService::class);
    $period1 = $fiscalYear->accountingPeriods()->where('period_number', 1)->firstOrFail();

    controlsPostApprovedJournal($service, $fiscalYear, $period1->id, [
        ['account_id' => $cash->id, 'debit' => 1000000],
        ['account_id' => $revenue->id, 'credit' => 1000000],
    ]);

    controlsPostApprovedJournal($service, $fiscalYear, $period1->id, [
        ['account_id' => $expense->id, 'debit' => 250000],
        ['account_id' => $cash->id, 'credit' => 250000],
    ]);

    $fiscalYear->accountingPeriods()->each(fn (AccountingPeriod $p) => app(PeriodService::class)->close($p));

    $result = app(YearEndService::class)->closeFiscalYear($fiscalYear);

    $fiscalYear->refresh();

    expect($result['net_profit'])->toBe(750000.0);
    expect($fiscalYear->status)->toBe(FiscalYearStatus::Closed);

    $closing = $result['closing_journal'];

    expect($closing->status)->toBe(JournalStatus::Posted);
    expect($closing->source)->toBe('year_end');

    $revenueBalance = $closing->lines()->where('account_id', $revenue->id)->first();
    $expenseBalance = $closing->lines()->where('account_id', $expense->id)->first();
    $equityBalance = $closing->lines()->where('account_id', $currentYearProfit->id)->first();

    expect((float) $revenueBalance->debit)->toBe(1000000.0);
    expect((float) $expenseBalance->credit)->toBe(250000.0);
    expect((float) $equityBalance->credit)->toBe(750000.0);

    expect((float) $closing->lines()->sum('debit'))->toEqual((float) $closing->lines()->sum('credit'));

    expect($result['next_fiscal_year']->year)->toBe(2027);
    expect($fiscalYear->accountingPeriods()->where('period_number', 12)->first()->status)->toBe(PeriodStatus::Closed);

    $openingBalances = $result['next_fiscal_year']->openingBalances()->get();

    expect($result['opening_balances_created'])->toBeGreaterThanOrEqual(2);
    expect((float) $openingBalances->sum('debit'))->toEqual((float) $openingBalances->sum('credit'));

    $carriedCash = $openingBalances->where('account_id', $cash->id)->first();
    $carriedEquity = $openingBalances->where('account_id', $currentYearProfit->id)->first();

    expect((float) $carriedCash->debit)->toBe(750000.0);
    expect((float) $carriedEquity->credit)->toBe(750000.0);

    expect($fiscalYear->audits()->where('action', 'fiscal_year_closed')->exists())->toBeTrue();
    expect($closing->audits()->where('action', 'year_end_closing_posted')->exists())->toBeTrue();
});

it('refuses to close a fiscal year still in progress or already closed', function () {
    [$company, $fiscalYear] = controlsCompanyAndYear();

    $cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();
    $currentYearProfit = Account::factory()->ofType(AccountType::Equity)->withCode('3300')->create();

    controlsMapStatementLine('BS-CYPL', StatementType::BalanceSheet, [$currentYearProfit]);

    $service = app(AccountingService::class);
    $period1 = $fiscalYear->accountingPeriods()->where('period_number', 1)->firstOrFail();

    $draft = app(AccountingService::class)->createJournal([
        'company_id' => $company->id,
        'accounting_period_id' => $period1->id,
        'journal_date' => '2026-01-05',
        'description' => 'Unposted draft that blocks year-end close',
        'lines' => [
            ['account_id' => $cash->id, 'debit' => 50000],
            ['account_id' => $revenue->id, 'credit' => 50000],
        ],
    ]);

    expect(fn () => app(YearEndService::class)->closeFiscalYear($fiscalYear))
        ->toThrow(InvalidArgumentException::class, 'unposted');

    $draft->delete();

    controlsPostApprovedJournal($service, $fiscalYear, $period1->id, [
        ['account_id' => $cash->id, 'debit' => 50000],
        ['account_id' => $revenue->id, 'credit' => 50000],
    ]);

    $fiscalYear->accountingPeriods()->where('status', 'open')->each(fn (AccountingPeriod $p) => app(PeriodService::class)->close($p));

    $result = app(YearEndService::class)->closeFiscalYear($fiscalYear);

    expect(fn () => app(YearEndService::class)->closeFiscalYear($fiscalYear))
        ->toThrow(InvalidArgumentException::class, 'open fiscal year');
});
