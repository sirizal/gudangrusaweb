<?php

namespace App\Filament\Purchasing\Resources\VendorBills\Pages;

use App\Filament\Purchasing\Resources\VendorBills\VendorBillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorBills extends ListRecords
{
    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
