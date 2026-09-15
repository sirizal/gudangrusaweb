<?php

namespace App\Filament\Accounting\Resources\CostCenters;

use App\Filament\Accounting\Resources\CostCenters\Pages\CreateCostCenter;
use App\Filament\Accounting\Resources\CostCenters\Pages\EditCostCenter;
use App\Filament\Accounting\Resources\CostCenters\Pages\ListCostCenters;
use App\Filament\Accounting\Resources\CostCenters\Schemas\CostCenterForm;
use App\Filament\Accounting\Resources\CostCenters\Tables\CostCentersTable;
use App\Models\CostCenter;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CostCenterResource extends Resource
{
    protected static ?string $model = CostCenter::class;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function form(Schema $schema): Schema
    {
        return CostCenterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CostCentersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCostCenters::route('/'),
            'create' => CreateCostCenter::route('/create'),
            'edit' => EditCostCenter::route('/{record}/edit'),
        ];
    }
}
