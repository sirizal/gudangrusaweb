<?php

namespace App\Filament\Geography\Resources\Districts;

use App\Filament\Geography\Resources\Districts\Pages\CreateDistrict;
use App\Filament\Geography\Resources\Districts\Pages\EditDistrict;
use App\Filament\Geography\Resources\Districts\Pages\ListDistricts;
use App\Filament\Geography\Resources\Districts\RelationManagers\SubDistrictsRelationManager;
use App\Filament\Geography\Resources\Districts\Schemas\DistrictForm;
use App\Filament\Geography\Resources\Districts\Tables\DistrictsTable;
use App\Models\District;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static UnitEnum|string|null $navigationGroup = 'Geography';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    public static function form(Schema $schema): Schema
    {
        return DistrictForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DistrictsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SubDistrictsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDistricts::route('/'),
            'create' => CreateDistrict::route('/create'),
            'edit' => EditDistrict::route('/{record}/edit'),
        ];
    }
}
