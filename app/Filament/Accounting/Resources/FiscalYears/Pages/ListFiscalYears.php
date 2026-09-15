<?php

namespace App\Filament\Accounting\Resources\FiscalYears\Pages;

use App\Filament\Accounting\Resources\FiscalYears\FiscalYearResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFiscalYears extends ListRecords
{
    protected static string $resource = FiscalYearResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
