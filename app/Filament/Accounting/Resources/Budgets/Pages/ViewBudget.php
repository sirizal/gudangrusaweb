<?php

namespace App\Filament\Accounting\Resources\Budgets\Pages;

use App\Filament\Accounting\Resources\Budgets\BudgetResource;
use App\Filament\Accounting\Resources\Budgets\Concerns\ExportsBudget;
use App\Filament\Accounting\Resources\Budgets\Concerns\HandlesBudgetWorkflow;
use App\Filament\Accounting\Resources\Budgets\Concerns\HydratesBudgetLines;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBudget extends ViewRecord
{
    use ExportsBudget, HandlesBudgetWorkflow, HydratesBudgetLines;

    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Edit budget')
                ->url(fn (): string => EditBudget::getUrl(['record' => $this->record]))
                ->visible(fn (): bool => auth()->user()?->can('update', $this->record) ?? false),
            ...$this->getBudgetExportActions(),
            ...$this->getBudgetWorkflowActions(),
        ];
    }
}
