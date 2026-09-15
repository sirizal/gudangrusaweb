<?php

namespace App\Filament\Geography\Resources\SubDistricts;

use App\Filament\Geography\Resources\SubDistricts\Pages\CreateSubDistrict;
use App\Filament\Geography\Resources\SubDistricts\Pages\EditSubDistrict;
use App\Filament\Geography\Resources\SubDistricts\Pages\ListSubDistricts;
use App\Filament\Geography\Resources\SubDistricts\RelationManagers\VillagesRelationManager;
use App\Filament\Geography\Resources\SubDistricts\Schemas\SubDistrictForm;
use App\Filament\Geography\Resources\SubDistricts\Tables\SubDistrictsTable;
use App\Models\SubDistrict;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SubDistrictResource extends Resource
{
    protected static ?string $model = SubDistrict::class;

    protected static UnitEnum|string|null $navigationGroup = 'Geography';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    public static function form(Schema $schema): Schema
    {
        return SubDistrictForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubDistrictsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VillagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubDistricts::route('/'),
            'create' => CreateSubDistrict::route('/create'),
            'edit' => EditSubDistrict::route('/{record}/edit'),
        ];
    }
}
