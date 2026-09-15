<?php

namespace App\Filament\Geography\Resources\Countries;

use App\Filament\Geography\Resources\Countries\Pages\CreateCountry;
use App\Filament\Geography\Resources\Countries\Pages\EditCountry;
use App\Filament\Geography\Resources\Countries\Pages\ListCountries;
use App\Filament\Geography\Resources\Countries\RelationManagers\ProvincesRelationManager;
use App\Filament\Geography\Resources\Countries\Schemas\CountryForm;
use App\Filament\Geography\Resources\Countries\Tables\CountriesTable;
use App\Models\Country;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static UnitEnum|string|null $navigationGroup = 'Geography';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAsiaAustralia;

    public static function form(Schema $schema): Schema
    {
        return CountryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProvincesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }
}
