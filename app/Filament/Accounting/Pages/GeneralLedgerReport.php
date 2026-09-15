<?php

namespace App\Filament\Accounting\Pages;

use App\Enums\Role;
use App\Filament\Accounting\Pages\Concerns\ExportsReports;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\FiscalYear;
use App\Models\Project;
use App\Services\Accounting\FinancialStatementService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class GeneralLedgerReport extends Page
{
    use ExportsReports;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static ?string $title = 'General Ledger';

    protected string $view = 'filament.pages.accounting.general-ledger-report';

    public ?int $accountId = null;

    public ?int $fiscalYearId = null;

    public ?string $from = null;

    public ?string $to = null;

    public ?int $costCenterId = null;

    public ?int $departmentId = null;

    public ?int $projectId = null;

    public function mount(): void
    {
        $fiscalYear = FiscalYear::query()->current()->first() ?? FiscalYear::query()->first();

        if (! $fiscalYear) {
            return;
        }

        $this->fiscalYearId ??= $fiscalYear->id;
        $this->accountId ??= Account::query()->where('is_postable', true)->orderBy('account_code')->first()?->id;
        $this->from ??= $fiscalYear->start_date->toDateString();
        $this->to ??= $fiscalYear->end_date->toDateString();
    }

    public function updatedFiscalYearId(): void
    {
        $fiscalYear = FiscalYear::find($this->fiscalYearId);

        $this->from = $fiscalYear?->start_date->toDateString();
        $this->to = $fiscalYear?->end_date->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        if (! $this->accountId || ! $this->from || ! $this->to) {
            return [];
        }

        return app(FinancialStatementService::class)->generalLedger(
            accountId: $this->accountId,
            fiscalYearId: $this->fiscalYearId,
            from: $this->from,
            to: $this->to,
            costCenterId: $this->costCenterId,
            departmentId: $this->departmentId,
            projectId: $this->projectId,
        );
    }

    /**
     * @return Collection<int, Account>
     */
    public function getAccounts(): Collection
    {
        return Account::query()
            ->where('is_postable', true)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();
    }

    /**
     * @return Collection<int, FiscalYear>
     */
    public function getFiscalYears(): Collection
    {
        return FiscalYear::query()->orderByDesc('year')->get();
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
