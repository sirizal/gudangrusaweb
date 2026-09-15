<?php

namespace App\Filament\Geography\Resources\Countries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('code')
                    ->label('Country code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(3)
                    ->helperText('ISO 3166-1 alpha-3 code, e.g. IDN.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
