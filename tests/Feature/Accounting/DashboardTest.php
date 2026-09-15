<?php

use App\Enums\AccountType;
use App\Enums\StatementType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Company;
use App\Models\FinancialStatementLine;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\BudgetService;
use App\Services\Accounting\DashboardService;
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
        'is_current' => true,
    ]);

    app(PeriodService::class)->generateForFiscalYear($this->fiscalYear);

    $this->cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create(['company_id' => $this->company->id]);
    $this->revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create(['company_id' => $this->company->id]);
    $this->expense = Account::factory()->ofType(AccountType::Expense)->withCode('6100')->create(['company_id' => $this->company->id]);

    $this->actor = User::factory()->create();

    app()->instance('test_company', $this->company);
    app()->instance('test_fiscal_year', $this->fiscalYear);
    app()->instance('test_actor', $this->actor);
});

function dashboardPostJournal(array $lines, string $date, int $periodNumber = 1): JournalEntry
{
    $service = app(AccountingService::class);
    $period = app('test_fiscal_year')->accountingPeriods()->where('period_number', $periodNumber)->firstOrFail();

    $journal = $service->createJournal([
        'company_id' => app('test_company')->id,
        'accounting_period_id' => $period->id,
        'journal_date' => $date,
        'description' => 'Dashboard actual '.$date,
        'lines' => $lines,
    ]);

    $service->submit($journal);
    $service->approve($journal);

    return $service->post($journal);
}

function dashboardLine(int $accountId, array $months = []): array
{
    $line = ['account_id' => $accountId];

    foreach ($months as $month => $amount) {
        $line['month_'.$month] = $amount;
    }

    return $line;
}

function mapDashboardLine(string $code, array $accounts): void
{
    $line = FinancialStatementLine::factory()->create([
        'statement_type' => StatementType::BalanceSheet,
        'code' => $code,
    ]);

    $line->accounts()->syncWithPivotValues(
        collect($accounts)->pluck('id')->all(),
        ['include_descendants' => false],
    );
}

function approveDashboardBudget(Budget $budget): Budget
{
    app(BudgetService::class)->submit($budget, app('test_actor'));
    app(BudgetService::class)->approve($budget, app('test_actor'));

    return $budget->refresh();
}

it('exposes the headline financial KPIs', function () {
    mapDashboardLine('BS-CASH', [$this->cash]);
    mapDashboardLine('BS-AR', [$this->cash]);

    dashboardPostJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $this->revenue->id, 'credit' => 1000000],
    ], '2026-01-15');

    $kpis = app(DashboardService::class)->kpis();

    expect($kpis['fiscal_year']->id)->toBe($this->fiscalYear->id);
    expect($kpis['revenue'])->toBe(1000000.0);
    expect($kpis['gross_profit'])->toBe(1000000.0);
    expect($kpis['operating_profit'])->toBe(1000000.0);
    expect($kpis['net_profit'])->toBe(1000000.0);
    expect($kpis['cash_balance'])->toBe(1000000.0);
    expect($kpis['ytd_revenue'])->toBe(1000000.0);
    expect($kpis['ytd_expense'])->toBe(0.0);
});

it('computes budget utilization from the active budget', function () {
    $budget = app(BudgetService::class)->create([
        'company_id' => $this->company->id,
        'budget_code' => 'BGT-UTIL',
        'budget_name' => 'Utilization budget',
        'fiscal_year_id' => $this->fiscalYear->id,
        'lines' => [
            dashboardLine($this->expense->id, [1 => 1000000]),
        ],
    ]);
    approveDashboardBudget($budget);

    dashboardPostJournal([
        ['account_id' => $this->expense->id, 'debit' => 250000],
        ['account_id' => $this->cash->id, 'credit' => 250000],
    ], '2026-01-15');

    $kpis = app(DashboardService::class)->kpis();

    expect($kpis['budget_utilization'])->toBe(25.0);
});

it('builds a monthly performance series across periods', function () {
    dashboardPostJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $this->revenue->id, 'credit' => 1000000],
    ], '2026-01-15', 1);

    dashboardPostJournal([
        ['account_id' => $this->cash->id, 'debit' => 500000],
        ['account_id' => $this->revenue->id, 'credit' => 500000],
    ], '2026-02-15', 2);

    $series = app(DashboardService::class)->monthlyPerformance();

    expect(count($series['labels']))->toBe(12);
    expect($series['revenue'][0])->toBe(1000000.0);
    expect($series['revenue'][1])->toBe(500000.0);
    expect($series['revenue'][2])->toBe(0.0);
});

it('builds a budget vs actual series from the active budget months', function () {
    $budget = app(BudgetService::class)->create([
        'company_id' => $this->company->id,
        'budget_code' => 'BGT-SERIES',
        'budget_name' => 'Series budget',
        'fiscal_year_id' => $this->fiscalYear->id,
        'lines' => [
            dashboardLine($this->expense->id, [1 => 1000000, 2 => 2000000]),
        ],
    ]);
    approveDashboardBudget($budget);

    dashboardPostJournal([
        ['account_id' => $this->expense->id, 'debit' => 400000],
        ['account_id' => $this->cash->id, 'credit' => 400000],
    ], '2026-01-15', 1);

    $series = app(DashboardService::class)->budgetVsActualSeries();

    expect($series['budget'][0])->toBe(1000000.0);
    expect($series['budget'][1])->toBe(2000000.0);
    expect($series['actual'][0])->toBe(400000.0);
    expect($series['actual'][1])->toBe(0.0);
});

it('tracks the cash balance across the year', function () {
    mapDashboardLine('BS-CASH', [$this->cash]);

    dashboardPostJournal([
        ['account_id' => $this->cash->id, 'debit' => 1000000],
        ['account_id' => $this->revenue->id, 'credit' => 1000000],
    ], '2026-01-15', 1);

    dashboardPostJournal([
        ['account_id' => $this->expense->id, 'debit' => 250000],
        ['account_id' => $this->cash->id, 'credit' => 250000],
    ], '2026-02-15', 2);

    $trend = app(DashboardService::class)->cashFlowTrend();

    expect($trend['cash'][0])->toBe(1000000.0);
    expect($trend['cash'][1])->toBe(750000.0);
});

it('surfaces a cash and unposted-journal alert', function () {
    mapDashboardLine('BS-CASH', [$this->cash]);

    $service = app(AccountingService::class);
    $period = $this->fiscalYear->accountingPeriods()->where('period_number', 1)->firstOrFail();

    $service->createJournal([
        'company_id' => $this->company->id,
        'accounting_period_id' => $period->id,
        'journal_date' => '2026-01-10',
        'description' => 'Draft alert journal',
        'lines' => [
            ['account_id' => $this->cash->id, 'debit' => 100000],
            ['account_id' => $this->revenue->id, 'credit' => 100000],
        ],
    ]);

    $alerts = app(DashboardService::class)->alerts();
    $titles = collect($alerts)->pluck('title')->all();

    expect($titles)->toContain('Unposted journals');
});
