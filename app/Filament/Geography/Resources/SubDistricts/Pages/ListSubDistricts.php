<?php

namespace App\Filament\Geography\Resources\SubDistricts\Pages;

use App\Filament\Geography\Resources\SubDistricts\SubDistrictResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubDistricts extends ListRecords
{
    protected static string $resource = SubDistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
