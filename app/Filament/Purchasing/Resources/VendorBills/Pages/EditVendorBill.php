<?php

namespace App\Filament\Purchasing\Resources\VendorBills\Pages;

use App\Filament\Purchasing\Resources\VendorBills\Concerns\HydratesVendorBillLines;
use App\Filament\Purchasing\Resources\VendorBills\Concerns\PostsVendorBill;
use App\Filament\Purchasing\Resources\VendorBills\VendorBillResource;
use App\Services\Purchasing\VendorBillService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVendorBill extends EditRecord
{
    use HydratesVendorBillLines, PostsVendorBill;

    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [...$this->getVendorBillActions()];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(VendorBillService::class)->update($record, $data, auth()->user());
    }
}
