<?php

namespace App\Filament\Accounting\Resources\Currencies;

use App\Filament\Accounting\Resources\Currencies\Pages\CreateCurrency;
use App\Filament\Accounting\Resources\Currencies\Pages\EditCurrency;
use App\Filament\Accounting\Resources\Currencies\Pages\ListCurrencies;
use App\Filament\Accounting\Resources\Currencies\Schemas\CurrencyForm;
use App\Filament\Accounting\Resources\Currencies\Tables\CurrenciesTable;
use App\Models\Currency;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function form(Schema $schema): Schema
    {
        return CurrencyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CurrenciesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCurrencies::route('/'),
            'create' => CreateCurrency::route('/create'),
            'edit' => EditCurrency::route('/{record}/edit'),
        ];
    }
}
