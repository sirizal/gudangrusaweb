<?php

namespace App\Filament\Geography\Resources\Villages\Pages;

use App\Filament\Geography\Resources\Villages\VillageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVillages extends ListRecords
{
    protected static string $resource = VillageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
