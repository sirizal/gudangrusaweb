<?php

use App\Enums\Role;
use App\Filament\Accounting\Pages\ImportBudget;
use App\Filament\Accounting\Widgets\AccountingAlerts;
use App\Filament\Accounting\Widgets\FinancialKpisOverview;
use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Company;
use App\Models\FinancialStatementLine;
use App\Models\FiscalYear;
use App\Models\Role as RoleModel;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\BudgetService;
use Database\Seeders\AccountingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(AccountingSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(
        RoleModel::where('code', Role::SuperAdmin->value)->firstOrFail(),
    );

    actingAs($this->admin);
});

it('registers the accounting dashboard widgets', function () {
    foreach ([
        'FinancialKpisOverview',
        'AccountingAlerts',
        'MonthlyPerformanceChart',
        'BudgetVsActualChart',
        'CashFlowTrendChart',
    ] as $widget) {
        get('/accounting')->assertOk()->assertSee($widget);
    }
});

it('renders the dashboard widgets end to end', function () {
    Livewire::test(FinancialKpisOverview::class)->assertSee('Revenue');
    Livewire::test(AccountingAlerts::class)->assertSee('No alerts.');
});

it('renders every accounting master-data index page', function () {
    foreach ([
        '/accounting/companies',
        '/accounting/currencies',
        '/accounting/fiscal-years',
        '/accounting/accounts',
        '/accounting/cost-centers',
        '/accounting/departments',
        '/accounting/projects',
        '/accounting/tax-codes',
        '/accounting/payment-terms',
        '/accounting/financial-statement-lines',
        '/accounting/journal-entries',
        '/accounting/opening-balances',
        '/accounting/budgets',
        '/accounting/import-budget',
        '/accounting/audit-logs',
        '/accounting/users',
        '/accounting/trial-balance-report',
        '/accounting/general-ledger-report',
        '/accounting/income-statement-report',
        '/accounting/balance-sheet-report',
        '/accounting/cash-flow-report',
        '/accounting/budget-vs-actual-report',
    ] as $url) {
        get($url)->assertOk();
    }
});

it('renders the company creation and edit pages with documents and BOD tabs', function () {
    $company = Company::query()->firstOrFail();

    get('/accounting/companies/create')->assertOk();
    get("/accounting/companies/{$company->id}/edit")->assertOk();
});

it('renders the fiscal year creation and edit pages', function () {
    $fiscalYear = FiscalYear::query()->firstOrFail();

    get('/accounting/fiscal-years/create')->assertOk();
    get("/accounting/fiscal-years/{$fiscalYear->id}/edit")->assertOk();
});

it('renders the account creation and edit pages', function () {
    $account = Account::query()->where('account_code', '1111')->firstOrFail();

    get('/accounting/accounts/create')->assertOk();
    get("/accounting/accounts/{$account->id}/edit")->assertOk();
});

it('renders every master-data creation page with its full-width grid form', function () {
    foreach ([
        'cost-centers',
        'departments',
        'projects',
        'opening-balances',
        'financial-statement-lines',
        'tax-codes',
    ] as $slug) {
        get("/accounting/{$slug}/create")->assertOk();
    }
});

it('renders the journal entry creation page', function () {
    get('/accounting/journal-entries/create')->assertOk();
});

it('renders the journal entry view page with workflow actions', function () {
    $period = AccountingPeriod::query()->firstOrFail();
    $cash = Account::query()->where('account_code', '1111')->firstOrFail();
    $revenue = Account::query()->where('account_code', '4100')->firstOrFail();

    $journal = app(AccountingService::class)->createJournal([
        'company_id' => Company::query()->firstOrFail()->id,
        'accounting_period_id' => $period->id,
        'journal_date' => $period->fiscalYear->start_date,
        'description' => 'Smoke test journal',
        'lines' => [
            ['account_id' => $cash->id, 'debit' => 100000],
            ['account_id' => $revenue->id, 'credit' => 100000],
        ],
    ]);

    get("/accounting/journal-entries/{$journal->id}")->assertOk();
});

it('renders the financial statement line edit page with its accounts relation', function () {
    $line = FinancialStatementLine::query()->where('code', 'BS-CASH')->firstOrFail();

    get("/accounting/financial-statement-lines/{$line->id}/edit")->assertOk();
});

it('shows the import budget link in the accounting navigation', function () {
    get('/accounting/budgets')
        ->assertOk()
        ->assertSee('Import Budget');
});

it('renders the import budget form with native Filament controls', function () {
    $html = Livewire::test(ImportBudget::class)->html();

    expect($html)
        ->toContain('fi-select-input')
        ->toContain('fi-fo-file-upload');
});

it('renders the budget create, edit, and view pages', function () {
    $account = Account::query()->where('account_code', '1111')->firstOrFail();
    $fiscalYear = FiscalYear::query()->firstOrFail();

    $budget = app(BudgetService::class)->create([
        'company_id' => Company::query()->firstOrFail()->id,
        'budget_code' => 'BGT-2026-900',
        'budget_name' => 'Operational Budget 2026',
        'fiscal_year_id' => $fiscalYear->id,
        'lines' => [
            [
                'account_id' => $account->id,
                'month_1' => 1000000,
                'month_2' => 1000000,
            ],
        ],
    ]);

    get('/accounting/budgets/create')->assertOk();
    get("/accounting/budgets/{$budget->id}/edit")->assertOk();
    get("/accounting/budgets/{$budget->id}")
        ->assertOk()
        ->assertSee('Edit budget');
});

it('renders the audit log index and a view page', function () {
    $fiscalYear = FiscalYear::query()->firstOrFail();
    $fiscalYear->recordAudit('fiscal_year_closed', ['closing_journal' => 'JV-TEST']);

    $audit = $fiscalYear->audits()->where('action', 'fiscal_year_closed')->latest()->firstOrFail();

    get('/accounting/audit-logs')->assertOk();
    get("/accounting/audit-logs/{$audit->id}")->assertOk();
});
