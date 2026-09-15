<?php

namespace App\Filament\Accounting\Resources\OpeningBalances;

use App\Filament\Accounting\Resources\OpeningBalances\Pages\CreateOpeningBalance;
use App\Filament\Accounting\Resources\OpeningBalances\Pages\EditOpeningBalance;
use App\Filament\Accounting\Resources\OpeningBalances\Pages\ListOpeningBalances;
use App\Filament\Accounting\Resources\OpeningBalances\Schemas\OpeningBalanceForm;
use App\Filament\Accounting\Resources\OpeningBalances\Tables\OpeningBalancesTable;
use App\Models\OpeningBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OpeningBalanceResource extends Resource
{
    protected static ?string $model = OpeningBalance::class;

    protected static UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    public static function form(Schema $schema): Schema
    {
        return OpeningBalanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpeningBalancesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpeningBalances::route('/'),
            'create' => CreateOpeningBalance::route('/create'),
            'edit' => EditOpeningBalance::route('/{record}/edit'),
        ];
    }
}
