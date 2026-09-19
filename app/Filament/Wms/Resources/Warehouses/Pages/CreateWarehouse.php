<?php

namespace App\Filament\Wms\Resources\Warehouses\Pages;

use App\Filament\Wms\Resources\Warehouses\WarehouseResource;
use App\Services\Wms\WmsNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateWarehouse extends CreateRecord
{
    protected static string $resource = WarehouseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['warehouse_code'] = app(WmsNumberGenerator::class)->nextWarehouseCode();

        return $data;
    }
}
