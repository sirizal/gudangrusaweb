<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\Project;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\BudgetService;
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

    $this->cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create(['company_id' => $this->company->id]);
    $this->revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create(['company_id' => $this->company->id]);
    $this->expense = Account::factory()->ofType(AccountType::Expense)->withCode('6100')->create(['company_id' => $this->company->id]);

    $this->costCenter = CostCenter::factory()->create(['company_id' => $this->company->id]);
    $this->department = Department::factory()->create(['company_id' => $this->company->id]);
    $this->project = Project::factory()->create(['company_id' => $this->company->id]);

    $this->actor = User::factory()->create();

    app()->instance('test_company', $this->company);
    app()->instance('test_fiscal_year', $this->fiscalYear);
    app()->instance('test_actor', $this->actor);
});

/**
 * @param  array<int, array<string, mixed>>  $lines
 */
function createBudgetLines(array $lines): Budget
{
    return app(BudgetService::class)->create([
        'company_id' => app('test_company')->id,
        'budget_code' => 'BGT-001',
        'budget_name' => 'Test budget',
        'fiscal_year_id' => app('test_fiscal_year')->id,
        'lines' => $lines,
    ]);
}

/**
 * @param  array<int|string, int|float>  $months
 * @return array<string, mixed>
 */
function reportLine(int $accountId, array $months = [], ?int $costCenterId = null, ?int $departmentId = null, ?int $projectId = null): array
{
    $data = ['account_id' => $accountId];

    foreach ($months as $month => $amount) {
        $data['month_'.$month] = $amount;
    }

    if ($costCenterId !== null) {
        $data['cost_center_id'] = $costCenterId;
    }

    if ($departmentId !== null) {
        $data['department_id'] = $departmentId;
    }

    if ($projectId !== null) {
        $data['project_id'] = $projectId;
    }

    return $data;
}

/**
 * @param  array<int, array<string, mixed>>  $lines
 */
function reportActual(array $lines, string $date, int $periodNumber = 1, bool $post = true): JournalEntry
{
    $service = app(AccountingService::class);
    $period = app('test_fiscal_year')->accountingPeriods()->where('period_number', $periodNumber)->firstOrFail();

    $journal = $service->createJournal([
        'company_id' => app('test_company')->id,
        'accounting_period_id' => $period->id,
        'journal_date' => $date,
        'description' => 'Actual '.$date,
        'lines' => $lines,
    ]);

    if (! $post) {
        return $journal;
    }

    $service->submit($journal);
    $service->approve($journal);

    return $service->post($journal);
}

function approveBudget(Budget $budget): Budget
{
    app(BudgetService::class)->submit($budget, app('test_actor'));
    app(BudgetService::class)->approve($budget, app('test_actor'));

    return $budget->refresh();
}

it('computes variance as budget minus actual for expense accounts', function () {
    $budget = createBudgetLines([
        reportLine($this->expense->id, [1 => 1000000]),
    ]);
    approveBudget($budget);

    reportActual([
        ['account_id' => $this->expense->id, 'debit' => 400000],
        ['account_id' => $this->cash->id, 'credit' => 400000],
    ], '2026-01-15');

    $report = app(BudgetService::class)->budgetVsActual($this->fiscalYear->id, 1, 'monthly');
    $row = $report['rows'][0];

    expect($row['budget'])->toBe(1000000.0);
    expect($row['actual'])->toBe(400000.0);
    expect($row['variance'])->toBe(600000.0);
    expect($row['variance_pct'])->toBe(60.0);
});

it('computes variance as actual minus budget for revenue accounts', function () {
    $budget = createBudgetLines([
        reportLine($this->revenue->id, [1 => 1000000]),
    ]);
    approveBudget($budget);

    reportActual([
        ['account_id' => $this->cash->id, 'debit' => 1200000],
        ['account_id' => $this->revenue->id, 'credit' => 1200000],
    ], '2026-01-15');

    $report = app(BudgetService::class)->budgetVsActual($this->fiscalYear->id, 1, 'monthly');
    $row = $report['rows'][0];

    expect($row['variance'])->toBe(200000.0);
});

it('restricts the actuals to the selected period in monthly range', function () {
    $budget = createBudgetLines([
        reportLine($this->expense->id, [1 => 1000000, 2 => 1000000]),
    ]);
    approveBudget($budget);

    reportActual([
        ['account_id' => $this->expense->id, 'debit' => 300000],
        ['account_id' => $this->cash->id, 'credit' => 300000],
    ], '2026-01-15', 1);

    reportActual([
        ['account_id' => $this->expense->id, 'debit' => 500000],
        ['account_id' => $this->cash->id, 'credit' => 500000],
    ], '2026-02-15', 2);

    $january = app(BudgetService::class)->budgetVsActual($this->fiscalYear->id, 1, 'monthly');
    expect($january['rows'][0]['budget'])->toBe(1000000.0);
    expect($january['rows'][0]['actual'])->toBe(300000.0);

    $february = app(BudgetService::class)->budgetVsActual($this->fiscalYear->id, 2, 'monthly');
    expect($february['rows'][0]['budget'])->toBe(1000000.0);
    expect($february['rows'][0]['actual'])->toBe(500000.0);
});

it('sums budget months up to the period in ytd range', function () {
    $budget = createBudgetLines([
        reportLine($this->expense->id, [1 => 1000000, 2 => 1500000, 3 => 2000000]),
    ]);
    approveBudget($budget);

    reportActual([
        ['account_id' => $this->expense->id, 'debit' => 400000],
        ['account_id' => $this->cash->id, 'credit' => 400000],
    ], '2026-01-15', 1);

    reportActual([
        ['account_id' => $this->expense->id, 'debit' => 1000000],
        ['account_id' => $this->cash->id, 'credit' => 1000000],
    ], '2026-02-15', 2);

    $report = app(BudgetService::class)->budgetVsActual($this->fiscalYear->id, 2, 'ytd');

    expect($report['rows'][0]['budget'])->toBe(2500000.0);
    expect($report['rows'][0]['actual'])->toBe(1400000.0);
    expect($report['rows'][0]['variance'])->toBe(1100000.0);
});

it('uses annual amounts for the full year range', function () {
    $budget = createBudgetLines([
        reportLine($this->expense->id, [1 => 1000000, 2 => 1500000, 3 => 2000000]),
    ]);
    approveBudget($budget);

    reportActual([
        ['account_id' => $this->expense->id, 'debit' => 250000],
        ['account_id' => $this->cash->id, 'credit' => 250000],
    ], '2026-03-15', 3);

    $report = app(BudgetService::class)->budgetVsActual($this->fiscalYear->id, range: 'full_year');

    expect($report['rows'][0]['budget'])->toBe(4500000.0);
    expect($report['rows'][0]['actual'])->toBe(250000.0);
    expect($report['rows'][0]['variance'])->toBe(4250000.0);
});

it('ignores draft and submitted journals when computing actuals', function () {
    $budget = createBudgetLines([
        reportLine($this->expense->id, [1 => 1000000]),
    ]);
    approveBudget($budget);

    reportActual([
        ['account_id' => $this->expense->id, 'debit' => 100000],
        ['account_id' => $this->cash->id, 'credit' => 100000],
    ], '2026-01-05', 1, post: false);

    $report = app(BudgetService::class)->budgetVsActual($this->fiscalYear->id, 1, 'monthly');

    expect($report['rows'][0]['actual'])->toBe(0.0);
});

it('returns an empty report when no approved or active budget exists', function () {
    $report = app(BudgetService::class)->budgetVsActual($this->fiscalYear->id, 1, 'monthly');

    expect($report['budget'])->toBeNull();
    expect($report['rows'])->toBeEmpty();
});

it('filters actuals by the budget line dimensions', function () {
    $budgeted = reportLine(
        $this->expense->id,
        [1 => 1000000],
        costCenterId: $this->costCenter->id,
        departmentId: $this->department->id,
        projectId: $this->project->id,
    );
    approveBudget(createBudgetLines([$budgeted]));

    $match = [
        'cost_center_id' => $this->costCenter->id,
        'department_id' => $this->department->id,
        'project_id' => $this->project->id,
    ];

    reportActual([
        ['account_id' => $this->expense->id, 'debit' => 300000, ...$match],
        ['account_id' => $this->cash->id, 'credit' => 300000],
    ], '2026-01-15');

    reportActual([
        ['account_id' => $this->expense->id, 'debit' => 900000],
        ['account_id' => $this->cash->id, 'credit' => 900000],
    ], '2026-01-20');

    $report = app(BudgetService::class)->budgetVsActual($this->fiscalYear->id, 1, 'monthly');

    expect($report['rows'][0]['actual'])->toBe(300000.0);
    expect($report['rows'][0]['variance'])->toBe(700000.0);
});
