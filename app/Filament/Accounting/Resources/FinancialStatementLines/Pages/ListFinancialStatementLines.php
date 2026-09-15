<?php

namespace App\Filament\Accounting\Resources\FinancialStatementLines\Pages;

use App\Filament\Accounting\Resources\FinancialStatementLines\FinancialStatementLineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFinancialStatementLines extends ListRecords
{
    protected static string $resource = FinancialStatementLineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
