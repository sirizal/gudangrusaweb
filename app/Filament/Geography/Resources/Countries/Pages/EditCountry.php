<?php

namespace App\Filament\Geography\Resources\Countries\Pages;

use App\Filament\Geography\Resources\Countries\CountryResource;
use Filament\Resources\Pages\EditRecord;

class EditCountry extends EditRecord
{
    protected static string $resource = CountryResource::class;
}
