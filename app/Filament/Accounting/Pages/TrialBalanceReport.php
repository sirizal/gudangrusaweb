<?php

namespace App\Filament\Accounting\Pages;

use App\Enums\Role;
use App\Filament\Accounting\Pages\Concerns\ExportsReports;
use App\Models\AccountingPeriod;
use App\Models\FiscalYear;
use App\Services\Accounting\FinancialStatementService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class TrialBalanceReport extends Page
{
    use ExportsReports;

    protected static string|UnitEnum|null $navigationGroup = 'Reports';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $title = 'Trial Balance';

    protected string $view = 'filament.pages.accounting.trial-balance-report';

    public ?int $fiscalYearId = null;

    public ?int $periodNumber = null;

    public ?string $asOfDate = null;

    public function mount(): void
    {
        $this->fiscalYearId ??= FiscalYear::query()->current()->first()?->id
            ?? FiscalYear::query()->first()?->id;
    }

    public function updatedFiscalYearId(): void
    {
        $this->periodNumber = null;
        $this->asOfDate = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        return app(FinancialStatementService::class)->trialBalance($this->fiscalYearId, $this->periodNumber, $this->asOfDate);
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

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole(Role::SuperAdmin, Role::FinanceManager, Role::Accountant, Role::Viewer) ?? false;
    }
}
