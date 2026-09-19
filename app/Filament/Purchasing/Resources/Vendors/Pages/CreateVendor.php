<?php

namespace App\Filament\Purchasing\Resources\Vendors\Pages;

use App\Filament\Purchasing\Resources\Vendors\VendorResource;
use App\Services\Purchasing\PurchasingNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateVendor extends CreateRecord
{
    protected static string $resource = VendorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_code'] = app(PurchasingNumberGenerator::class)->nextVendorCode();

        return $data;
    }
}
