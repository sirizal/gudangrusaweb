<?php

namespace App\Filament\Wms\Resources\InventoryStocks;

use App\Filament\Wms\Resources\InventoryStocks\Pages\ListInventoryStocks;
use App\Filament\Wms\Resources\InventoryStocks\Pages\ViewInventoryStock;
use App\Filament\Wms\Resources\InventoryStocks\Schemas\InventoryStockForm;
use App\Filament\Wms\Resources\InventoryStocks\Tables\InventoryStocksTable;
use App\Models\InventoryStock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InventoryStockResource extends Resource
{
    protected static ?string $model = InventoryStock::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function form(Schema $schema): Schema
    {
        return InventoryStockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryStocksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryStocks::route('/'),
            'view' => ViewInventoryStock::route('/{record}'),
        ];
    }
}
