<?php

namespace App\Filament\Wms\Resources\InventoryStocks\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InventoryStockForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(2)->schema([
            TextInput::make('product.name')->label('Product'),
            TextInput::make('warehouse.name')->label('Warehouse'),
            TextInput::make('location.location_code')->label('Location'),
            TextInput::make('quantity')->numeric(),
            TextInput::make('reserved_quantity')->numeric(),
        ]);
    }
}
