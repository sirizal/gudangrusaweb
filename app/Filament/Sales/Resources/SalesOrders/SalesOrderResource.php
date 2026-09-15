<?php

namespace App\Filament\Sales\Resources\SalesOrders;

use App\Filament\Sales\Resources\SalesOrders\Pages\CreateSalesOrder;
use App\Filament\Sales\Resources\SalesOrders\Pages\EditSalesOrder;
use App\Filament\Sales\Resources\SalesOrders\Pages\ListSalesOrders;
use App\Filament\Sales\Resources\SalesOrders\Pages\ViewSalesOrder;
use App\Filament\Sales\Resources\SalesOrders\Schemas\SalesOrderForm;
use App\Filament\Sales\Resources\SalesOrders\Tables\SalesOrdersTable;
use App\Models\SalesOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SalesOrderResource extends Resource
{
    protected static ?string $model = SalesOrder::class;

    protected static UnitEnum|string|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    public static function form(Schema $schema): Schema
    {
        return SalesOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesOrders::route('/'),
            'create' => CreateSalesOrder::route('/create'),
            'edit' => EditSalesOrder::route('/{record}/edit'),
            'view' => ViewSalesOrder::route('/{record}'),
        ];
    }
}
