<?php

namespace App\Filament\Purchasing\Resources\PurchaseOrders;

use App\Filament\Purchasing\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Purchasing\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Purchasing\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Purchasing\Resources\PurchaseOrders\Pages\ViewPurchaseOrder;
use App\Filament\Purchasing\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Purchasing\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
            'view' => ViewPurchaseOrder::route('/{record}'),
        ];
    }
}
