<?php

namespace App\Filament\Sales\Resources\Customers\Pages;

use App\Filament\Sales\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;
}
