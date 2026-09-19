<?php

namespace App\Filament\Purchasing\Resources\VendorBills\Pages;

use App\Enums\VendorBillStatus;
use App\Filament\Purchasing\Resources\VendorBills\Concerns\HydratesVendorBillLines;
use App\Filament\Purchasing\Resources\VendorBills\Concerns\PostsVendorBill;
use App\Filament\Purchasing\Resources\VendorBills\VendorBillResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewVendorBill extends ViewRecord
{
    use HydratesVendorBillLines, PostsVendorBill;

    protected static string $resource = VendorBillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->url(fn (): string => EditVendorBill::getUrl(['record' => $this->record]))
                ->visible(fn (): bool => $this->record->status === VendorBillStatus::Draft),
            ...$this->getVendorBillActions(),
        ];
    }
}
