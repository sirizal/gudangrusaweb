<?php

use App\Enums\AccountType;
use App\Enums\BudgetStatus;
use App\Filament\Accounting\Resources\Budgets\Pages\ViewBudget;
use App\Models\Account;
use App\Models\Budget;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->fiscalYear = FiscalYear::factory()->create([
        'company_id' => $this->company->id,
        'year' => 2026,
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
    ]);

    $this->actor = User::factory()->create();

    $this->cash = Account::factory()->ofType(AccountType::Asset)->withCode('1111')->create();
    $this->revenue = Account::factory()->ofType(AccountType::Revenue)->withCode('4100')->create();
    $this->group = Account::factory()->ofType(AccountType::Asset)->withCode('1000')->group()->create();

    app()->instance('test_company', $this->company);
    app()->instance('test_fiscal_year', $this->fiscalYear);
});

function createBudget(array $lines = [], ?string $code = 'BGT-001', ?FiscalYear $fiscalYear = null): Budget
{
    return app(BudgetService::class)->create([
        'company_id' => $fiscalYear?->company_id ?? app('test_company')->id,
        'budget_code' => $code,
        'budget_name' => 'Test budget',
        'fiscal_year_id' => $fiscalYear?->id ?? app('test_fiscal_year')->id,
        'lines' => $lines,
    ]);
}

function line(int $accountId, array $months = []): array
{
    $line = ['account_id' => $accountId];

    foreach ($months as $month => $amount) {
        $line['month_'.$month] = $amount;
    }

    return $line;
}

it('derives the annual amount from the sum of the twelve months', function () {
    $budget = createBudget([
        line($this->cash->id, [1 => 1000000, 2 => 2000000, 6 => 3000000]),
        line($this->revenue->id, [1 => 2500000, 12 => 3500000]),
    ]);

    $budget->load('lines.months');

    expect($budget->lines)->toHaveCount(2);
    expect((float) $budget->lines[0]->annual_amount)->toBe(6000000.0);
    expect((float) $budget->lines[1]->annual_amount)->toBe(6000000.0);
    expect($budget->lines->flatMap->months)->toHaveCount(24);
});

it('creates every budget line month even when the amount is zero', function () {
    $budget = createBudget([line($this->cash->id)]);

    $budget->load('lines.months');

    expect($budget->lines->first()->months)->toHaveCount(12);
});

it('rejects a budget line with a group account', function () {
    expect(fn () => createBudget([line($this->group->id)]))
        ->toThrow(InvalidArgumentException::class, 'postable, non-group');
});

it('rejects duplicate budget lines for the same account and dimensions', function () {
    expect(fn () => createBudget([
        line($this->cash->id, [1 => 1000000]),
        line($this->cash->id, [2 => 500000]),
    ]))->toThrow(InvalidArgumentException::class, 'Duplicate budget line');
});

it('rejects a budget with a negative monthly amount', function () {
    expect(fn () => createBudget([line($this->cash->id, [1 => -100])]))
        ->toThrow(InvalidArgumentException::class, 'not be negative');
});

it('rejects a budget without any lines', function () {
    expect(fn () => createBudget([]))
        ->toThrow(InvalidArgumentException::class, 'at least one line');
});

it('submits a draft budget', function () {
    $budget = createBudget([line($this->cash->id, [1 => 1000000])]);

    app(BudgetService::class)->submit($budget, $this->actor);

    expect($budget->refresh()->status)->toBe(BudgetStatus::Submitted);
    expect($budget->submitted_by)->toBe($this->actor->id);
    expect($budget->submitted_at)->not->toBeNull();
});

it('allows a rejected budget to be resubmitted', function () {
    $budget = createBudget([line($this->cash->id, [1 => 1000000])]);

    app(BudgetService::class)->submit($budget, $this->actor);
    app(BudgetService::class)->reject($budget, $this->actor);
    app(BudgetService::class)->submit($budget, $this->actor);

    expect($budget->refresh()->status)->toBe(BudgetStatus::Submitted);
});

it('approves a submitted budget and marks it active', function () {
    $budget = createBudget([line($this->cash->id, [1 => 1000000])]);

    app(BudgetService::class)->submit($budget, $this->actor);
    app(BudgetService::class)->approve($budget, $this->actor);

    expect($budget->refresh()->status)->toBe(BudgetStatus::Approved);
    expect($budget->is_active)->toBeTrue();
    expect($budget->approved_by)->toBe($this->actor->id);
    expect($budget->approved_at)->not->toBeNull();
});

it('keeps only one active budget per fiscal year', function () {
    $first = createBudget([line($this->cash->id)], code: 'BGT-001', fiscalYear: $this->fiscalYear);
    $second = createBudget([line($this->revenue->id)], code: 'BGT-002', fiscalYear: $this->fiscalYear);

    app(BudgetService::class)->submit($first, $this->actor);
    app(BudgetService::class)->approve($first, $this->actor);
    app(BudgetService::class)->submit($second, $this->actor);
    app(BudgetService::class)->approve($second, $this->actor);

    expect($first->refresh()->is_active)->toBeFalse();
    expect($second->refresh()->is_active)->toBeTrue();
    expect(Budget::query()->activeBudget()->count())->toBe(1);
});

it('cannot update an approved budget', function () {
    $budget = createBudget([line($this->cash->id, [1 => 1000000])]);

    app(BudgetService::class)->submit($budget, $this->actor);
    app(BudgetService::class)->approve($budget, $this->actor);

    expect(fn () => app(BudgetService::class)->update($budget, ['budget_name' => 'Changed', 'lines' => [line($this->cash->id, [1 => 1])]], $this->actor))
        ->toThrow(InvalidArgumentException::class, 'current status');
});

it('cannot approve a draft budget before it is submitted', function () {
    $budget = createBudget([line($this->cash->id, [1 => 1000000])]);

    expect(fn () => app(BudgetService::class)->approve($budget, $this->actor))
        ->toThrow(InvalidArgumentException::class, 'current status');
});

it('locks an approved budget', function () {
    $budget = createBudget([line($this->cash->id, [1 => 1000000])]);

    app(BudgetService::class)->submit($budget, $this->actor);
    app(BudgetService::class)->approve($budget, $this->actor);
    app(BudgetService::class)->lock($budget, $this->actor);

    expect($budget->refresh()->status)->toBe(BudgetStatus::Locked);
});

it('creates a revision that copies monthly values and increments the version', function () {
    $budget = createBudget([line($this->cash->id, [1 => 1000000, 5 => 2500000])], code: 'BGT-001');

    app(BudgetService::class)->submit($budget, $this->actor);
    app(BudgetService::class)->approve($budget, $this->actor);

    $revision = app(BudgetService::class)->revise($budget, $this->actor);

    expect($revision->version)->toBe('V2');
    expect($revision->status)->toBe(BudgetStatus::Draft);
    expect($revision->budget_code)->toBe('BGT-001-V2');
    expect($revision->is_active)->toBeFalse();
    expect($budget->refresh()->status)->toBe(BudgetStatus::Approved);

    $revision->load('lines.months');
    $copiedLine = $revision->lines->first();

    expect((float) $copiedLine->annual_amount)->toBe(3500000.0);
    expect((float) $copiedLine->months->firstWhere('month', 1)->amount)->toBe(1000000.0);
    expect((float) $copiedLine->months->firstWhere('month', 5)->amount)->toBe(2500000.0);
    expect($revision->lines->flatMap->months)->toHaveCount(12);
});

it('cannot revise a draft budget', function () {
    $budget = createBudget([line($this->cash->id, [1 => 1000000])]);

    expect(fn () => app(BudgetService::class)->revise($budget, $this->actor))
        ->toThrow(InvalidArgumentException::class, 'current status');
});

it('hydrates the budget lines on the view page', function () {
    $admin = User::factory()->create();
    $admin->roles()->attach(
        Role::where('code', App\Enums\Role::SuperAdmin->value)->firstOrCreate(
            ['code' => App\Enums\Role::SuperAdmin->value],
            ['name' => App\Enums\Role::SuperAdmin->getLabel()],
        ),
    );

    Pest\Laravel\actingAs($admin);
    filament()->setCurrentPanel('accounting');

    $budget = createBudget([
        line($this->cash->id, [1 => 1000000, 2 => 500000]),
        line($this->revenue->id, [1 => 2500000]),
    ]);

    $data = Livewire::test(ViewBudget::class, [
        'record' => $budget->getRouteKey(),
    ])->get('data');

    $lines = array_values($data['lines']);

    expect($lines)->toHaveCount(2)
        ->and((string) $lines[0]['account_id'])->toBe((string) $this->cash->id)
        ->and($lines[0]['month_1'])->toBe(1000000.0)
        ->and($lines[0]['month_2'])->toBe(500000.0)
        ->and((string) $lines[1]['account_id'])->toBe((string) $this->revenue->id)
        ->and($lines[1]['month_1'])->toBe(2500000.0);
});
