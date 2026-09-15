<?php

namespace App\Filament\Accounting\Resources\PaymentTerms\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PaymentTermForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20)
                    ->dehydrateStateUsing(fn (string $state): string => strtoupper($state)),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('due_days')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->suffix('days'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
