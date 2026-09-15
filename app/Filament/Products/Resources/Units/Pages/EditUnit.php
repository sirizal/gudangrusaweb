<?php

namespace App\Filament\Products\Resources\Units\Pages;

use App\Filament\Products\Resources\Units\UnitResource;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
