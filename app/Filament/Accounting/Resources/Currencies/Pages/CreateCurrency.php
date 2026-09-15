<?php

namespace App\Filament\Accounting\Resources\Currencies\Pages;

use App\Filament\Accounting\Resources\Currencies\CurrencyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;
}
