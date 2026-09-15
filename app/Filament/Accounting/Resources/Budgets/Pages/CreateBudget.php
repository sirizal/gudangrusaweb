<?php

namespace App\Filament\Accounting\Resources\Budgets\Pages;

use App\Filament\Accounting\Resources\Budgets\BudgetResource;
use App\Services\Accounting\BudgetService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateBudget extends CreateRecord
{
    protected static string $resource = BudgetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(BudgetService::class)->create($data, auth()->user());
    }
}
