<?php

namespace App\Filament\Accounting\Resources\Currencies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(3)
                    ->dehydrateStateUsing(fn (string $state): string => strtoupper($state)),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('symbol')
                    ->maxLength(5),
                TextInput::make('decimal_places')
                    ->numeric()
                    ->default(2)
                    ->minValue(0)
                    ->maxValue(4),
                Toggle::make('is_base_currency')
                    ->default(false),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
