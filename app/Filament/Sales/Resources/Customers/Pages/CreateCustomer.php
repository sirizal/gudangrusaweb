<?php

namespace App\Filament\Sales\Resources\Customers\Pages;

use App\Filament\Sales\Resources\Customers\CustomerResource;
use App\Services\Sales\SalesNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['customer_code'] = app(SalesNumberGenerator::class)->nextCustomerCode();

        return $data;
    }
}
