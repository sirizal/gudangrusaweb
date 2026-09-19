<?php

namespace App\Filament\Wms\Resources\OutboundShipments\Pages;

use App\Filament\Wms\Resources\OutboundShipments\OutboundShipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOutboundShipments extends ListRecords
{
    protected static string $resource = OutboundShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
