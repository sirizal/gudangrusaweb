<?php

namespace App\Filament\Accounting\Resources\Budgets\Concerns;

use App\Models\Budget;
use App\Services\Accounting\BudgetExportService;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait ExportsBudget
{
    /**
     * @return array<int, Action>
     */
    protected function getBudgetExportActions(): array
    {
        return [
            Action::make('export-budget-csv')
                ->label('Export CSV')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => $this->getBudgetForExport() !== null)
                ->action(fn (): StreamedResponse => app(BudgetExportService::class)->csv($this->getBudgetForExport())),

            Action::make('export-budget-xlsx')
                ->label('Export Excel')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => $this->getBudgetForExport() !== null)
                ->action(fn (): StreamedResponse => app(BudgetExportService::class)->xlsx($this->getBudgetForExport())),
        ];
    }

    protected function getBudgetForExport(): Budget
    {
        return $this->record;
    }
}
