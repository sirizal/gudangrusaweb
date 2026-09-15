<?php

namespace App\Filament\Accounting\Pages;

use App\Enums\Role;
use App\Filament\Accounting\Pages\Concerns\ExportsReports;
use App\Models\AccountingPeriod;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\Project;
use App\Services\Accounting\BudgetService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class BudgetVsActualReport extends Page
{
    use ExportsReports;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $title = 'Budget vs Actual';

    protected string $view = 'filament.pages.accounting.budget-vs-actual-report';

    public ?int $fiscalYearId = null;

    public string $range = 'monthly';

    public ?int $periodNumber = null;

    public ?int $costCenterId = null;

    public ?int $departmentId = null;

    public ?int $projectId = null;

    public function mount(): void
    {
        $this->fiscalYearId ??= FiscalYear::query()->current()->first()?->id
            ?? FiscalYear::query()->first()?->id;
    }

    public function updatedFiscalYearId(): void
    {
        $this->periodNumber = null;
    }

    public function updatedRange(): void
    {
        $this->periodNumber = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        return app(BudgetService::class)->budgetVsActual(
            fiscalYearId: $this->fiscalYearId,
            periodNumber: $this->periodNumber,
            range: $this->range,
            costCenterId: $this->costCenterId,
            departmentId: $this->departmentId,
            projectId: $this->projectId,
        );
    }

    /**
     * @return Collection<int, FiscalYear>
     */
    public function getFiscalYears(): Collection
    {
        return FiscalYear::query()->orderByDesc('year')->get();
    }

    /**
     * @return Collection<int, AccountingPeriod>
     */
    public function getPeriods(): Collection
    {
        if (! $this->fiscalYearId) {
            return collect();
        }

        return AccountingPeriod::query()
            ->where('fiscal_year_id', $this->fiscalYearId)
            ->orderBy('period_number')
            ->get();
    }

    /**
     * @return Collection<int, CostCenter>
     */
    public function getCostCenters(): Collection
    {
        return CostCenter::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Department>
     */
    public function getDepartments(): Collection
    {
        return Department::query()->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return Project::query()->orderBy('name')->get();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(Role::SuperAdmin, Role::FinanceManager, Role::Accountant, Role::Viewer) ?? false;
    }
}
