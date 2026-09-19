<?php

namespace App\Filament\Wms\Resources\StockAdjustments;

use App\Filament\Wms\Resources\StockAdjustments\Pages\CreateStockAdjustment;
use App\Filament\Wms\Resources\StockAdjustments\Pages\ListStockAdjustments;
use App\Filament\Wms\Resources\StockAdjustments\Pages\ViewStockAdjustment;
use App\Filament\Wms\Resources\StockAdjustments\Schemas\StockAdjustmentForm;
use App\Filament\Wms\Resources\StockAdjustments\Tables\StockAdjustmentsTable;
use App\Models\StockAdjustment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class StockAdjustmentResource extends Resource
{
    protected static ?string $model = StockAdjustment::class;

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function form(Schema $schema): Schema
    {
        return StockAdjustmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockAdjustmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockAdjustments::route('/'),
            'create' => CreateStockAdjustment::route('/create'),
            'view' => ViewStockAdjustment::route('/{record}'),
        ];
    }
}
