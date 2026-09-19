<?php

namespace App\Filament\Purchasing\Resources\VendorBills\Pages;

use App\Filament\Purchasing\Resources\VendorBills\VendorBillResource;
use App\Services\Purchasing\VendorBillService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVendorBill extends CreateRecord
{
    protected static string $resource = VendorBillResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(VendorBillService::class)->create($data, auth()->user());
    }
}
