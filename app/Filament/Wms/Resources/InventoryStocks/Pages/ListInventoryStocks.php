<?php

namespace App\Filament\Wms\Resources\InventoryStocks\Pages;

use App\Filament\Wms\Resources\InventoryStocks\InventoryStockResource;
use Filament\Resources\Pages\ListRecords;

class ListInventoryStocks extends ListRecords
{
    protected static string $resource = InventoryStockResource::class;
}
