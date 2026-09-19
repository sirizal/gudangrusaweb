<?php

namespace App\Filament\Wms\Resources\StockTransfers;

use App\Filament\Wms\Resources\StockTransfers\Pages\CreateStockTransfer;
use App\Filament\Wms\Resources\StockTransfers\Pages\ListStockTransfers;
use App\Filament\Wms\Resources\StockTransfers\Pages\ViewStockTransfer;
use App\Filament\Wms\Resources\StockTransfers\Schemas\StockTransferForm;
use App\Filament\Wms\Resources\StockTransfers\Tables\StockTransfersTable;
use App\Models\StockTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StockTransferResource extends Resource
{
    protected static ?string $model = StockTransfer::class;

    protected static UnitEnum|string|null $navigationGroup = 'Warehouse';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsUpDown;

    public static function form(Schema $schema): Schema
    {
        return StockTransferForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockTransfersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockTransfers::route('/'),
            'create' => CreateStockTransfer::route('/create'),
            'view' => ViewStockTransfer::route('/{record}'),
        ];
    }
}
