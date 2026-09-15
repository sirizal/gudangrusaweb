<?php

namespace App\Filament\Products\Resources\Categories\Pages;

use App\Filament\Products\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;
}
