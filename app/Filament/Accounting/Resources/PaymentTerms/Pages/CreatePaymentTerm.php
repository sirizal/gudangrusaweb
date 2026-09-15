<?php

namespace App\Filament\Accounting\Resources\PaymentTerms\Pages;

use App\Filament\Accounting\Resources\PaymentTerms\PaymentTermResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentTerm extends CreateRecord
{
    protected static string $resource = PaymentTermResource::class;
}
