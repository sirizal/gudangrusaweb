<?php

namespace App\Filament\Wms\Resources\Warehouses;

use App\Filament\Wms\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Wms\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Wms\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Wms\Resources\Warehouses\RelationManagers\WarehouseLocationsRelationManager;
use App\Filament\Wms\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Wms\Resources\Warehouses\Tables\WarehousesTable;
use App\Models\Warehouse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static UnitEnum|string|null $navigationGroup = 'Warehouse';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [WarehouseLocationsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
