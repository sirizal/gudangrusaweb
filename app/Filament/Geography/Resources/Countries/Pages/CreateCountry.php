<?php

namespace App\Filament\Geography\Resources\Countries\Pages;

use App\Filament\Geography\Resources\Countries\CountryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCountry extends CreateRecord
{
    protected static string $resource = CountryResource::class;
}
