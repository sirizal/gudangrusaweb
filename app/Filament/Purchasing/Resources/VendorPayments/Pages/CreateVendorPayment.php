<?php

namespace App\Filament\Purchasing\Resources\VendorPayments\Pages;

use App\Filament\Purchasing\Resources\VendorPayments\VendorPaymentResource;
use App\Models\Vendor;
use App\Services\Purchasing\VendorPaymentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateVendorPayment extends CreateRecord
{
    protected static string $resource = VendorPaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $vendor = Vendor::findOrFail($data['vendor_id']);

        return app(VendorPaymentService::class)->pay($vendor, $data, auth()->user());
    }
}
