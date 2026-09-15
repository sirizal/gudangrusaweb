<?php

namespace App\Filament\Accounting\Resources\Projects\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
                TextInput::make('code')
                    ->required()
                    ->maxLength(50),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
