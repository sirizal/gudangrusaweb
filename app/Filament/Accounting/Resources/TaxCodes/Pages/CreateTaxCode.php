<?php

namespace App\Filament\Accounting\Resources\TaxCodes\Pages;

use App\Filament\Accounting\Resources\TaxCodes\TaxCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxCode extends CreateRecord
{
    protected static string $resource = TaxCodeResource::class;
}
