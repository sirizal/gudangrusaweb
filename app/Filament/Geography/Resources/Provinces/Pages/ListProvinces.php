<?php

namespace App\Filament\Geography\Resources\Provinces\Pages;

use App\Filament\Geography\Resources\Provinces\ProvinceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProvinces extends ListRecords
{
    protected static string $resource = ProvinceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
