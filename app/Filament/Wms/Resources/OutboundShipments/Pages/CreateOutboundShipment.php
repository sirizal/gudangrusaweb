<?php

namespace App\Filament\Wms\Resources\OutboundShipments\Pages;

use App\Filament\Wms\Resources\OutboundShipments\OutboundShipmentResource;
use App\Models\SalesOrder;
use App\Services\Wms\OutboundService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateOutboundShipment extends CreateRecord
{
    protected static string $resource = OutboundShipmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $order = SalesOrder::findOrFail($data['sales_order_id']);

        return app(OutboundService::class)->ship($order, $data, auth()->user());
    }
}
