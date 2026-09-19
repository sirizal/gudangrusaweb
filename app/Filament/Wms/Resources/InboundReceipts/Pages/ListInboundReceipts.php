<?php

namespace App\Filament\Wms\Resources\InboundReceipts\Pages;

use App\Filament\Wms\Resources\InboundReceipts\InboundReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInboundReceipts extends ListRecords
{
    protected static string $resource = InboundReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
