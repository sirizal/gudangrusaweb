<?php

namespace App\Filament\Purchasing\Resources\GoodsReceipts;

use App\Filament\Purchasing\Resources\GoodsReceipts\Pages\ListGoodsReceipts;
use App\Filament\Purchasing\Resources\GoodsReceipts\Pages\ViewGoodsReceipt;
use App\Filament\Purchasing\Resources\GoodsReceipts\Schemas\GoodsReceiptForm;
use App\Filament\Purchasing\Resources\GoodsReceipts\Tables\GoodsReceiptsTable;
use App\Models\GoodsReceipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GoodsReceiptResource extends Resource
{
    protected static ?string $model = GoodsReceipt::class;

    protected static UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    public static function form(Schema $schema): Schema
    {
        return GoodsReceiptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GoodsReceiptsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGoodsReceipts::route('/'),
            'view' => ViewGoodsReceipt::route('/{record}'),
        ];
    }
}
