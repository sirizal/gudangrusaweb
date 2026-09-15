<?php

namespace App\Filament\Accounting\Resources\Budgets\Pages;

use App\Enums\BudgetStatus;
use App\Filament\Accounting\Resources\Budgets\BudgetResource;
use App\Filament\Accounting\Resources\Budgets\Concerns\ExportsBudget;
use App\Filament\Accounting\Resources\Budgets\Concerns\HandlesBudgetWorkflow;
use App\Filament\Accounting\Resources\Budgets\Concerns\HydratesBudgetLines;
use App\Services\Accounting\BudgetService;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditBudget extends EditRecord
{
    use ExportsBudget, HandlesBudgetWorkflow, HydratesBudgetLines;

    protected static string $resource = BudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getBudgetExportActions(),
            ...$this->getBudgetWorkflowActions(),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $schema = parent::form($schema);

        if (in_array($this->record->status, [BudgetStatus::Submitted, BudgetStatus::Approved, BudgetStatus::Locked], true)) {
            return $schema->disabled();
        }

        return $schema;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(BudgetService::class)->update($record, $data, auth()->user());
    }
}
