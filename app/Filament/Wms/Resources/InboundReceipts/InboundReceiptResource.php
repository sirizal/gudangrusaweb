<?php

namespace App\Filament\Wms\Resources\InboundReceipts;

use App\Filament\Wms\Resources\InboundReceipts\Pages\CreateInboundReceipt;
use App\Filament\Wms\Resources\InboundReceipts\Pages\ListInboundReceipts;
use App\Filament\Wms\Resources\InboundReceipts\Pages\ViewInboundReceipt;
use App\Filament\Wms\Resources\InboundReceipts\Schemas\InboundReceiptForm;
use App\Filament\Wms\Resources\InboundReceipts\Tables\InboundReceiptsTable;
use App\Models\InboundReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InboundReceiptResource extends Resource
{
    protected static ?string $model = InboundReceipt::class;

    protected static UnitEnum|string|null $navigationGroup = 'Warehouse';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    public static function form(Schema $schema): Schema
    {
        return InboundReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InboundReceiptsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInboundReceipts::route('/'),
            'create' => CreateInboundReceipt::route('/create'),
            'view' => ViewInboundReceipt::route('/{record}'),
        ];
    }
}
