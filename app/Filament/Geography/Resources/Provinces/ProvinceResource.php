<?php

namespace App\Filament\Geography\Resources\Provinces;

use App\Filament\Geography\Resources\Provinces\Pages\CreateProvince;
use App\Filament\Geography\Resources\Provinces\Pages\EditProvince;
use App\Filament\Geography\Resources\Provinces\Pages\ListProvinces;
use App\Filament\Geography\Resources\Provinces\RelationManagers\DistrictsRelationManager;
use App\Filament\Geography\Resources\Provinces\Schemas\ProvinceForm;
use App\Filament\Geography\Resources\Provinces\Tables\ProvincesTable;
use App\Models\Province;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProvinceResource extends Resource
{
    protected static ?string $model = Province::class;

    protected static UnitEnum|string|null $navigationGroup = 'Geography';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    public static function form(Schema $schema): Schema
    {
        return ProvinceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProvincesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            DistrictsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProvinces::route('/'),
            'create' => CreateProvince::route('/create'),
            'edit' => EditProvince::route('/{record}/edit'),
        ];
    }
}
