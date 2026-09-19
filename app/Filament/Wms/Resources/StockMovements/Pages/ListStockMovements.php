<?php

namespace App\Filament\Wms\Resources\StockMovements\Pages;

use App\Filament\Wms\Resources\StockMovements\StockMovementResource;
use Filament\Resources\Pages\ListRecords;

class ListStockMovements extends ListRecords
{
    protected static string $resource = StockMovementResource::class;
}
