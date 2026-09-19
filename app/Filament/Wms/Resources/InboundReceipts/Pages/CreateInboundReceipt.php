<?php

namespace App\Filament\Wms\Resources\InboundReceipts\Pages;

use App\Filament\Wms\Resources\InboundReceipts\InboundReceiptResource;
use App\Models\PurchaseOrder;
use App\Services\Wms\InboundService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInboundReceipt extends CreateRecord
{
    protected static string $resource = InboundReceiptResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $order = PurchaseOrder::findOrFail($data['purchase_order_id']);

        return app(InboundService::class)->receive($order, $data, auth()->user());
    }
}
