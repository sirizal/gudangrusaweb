<?php

namespace App\Filament\Accounting\Resources\FiscalYears\Schemas;

use App\Enums\FiscalYearStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FiscalYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                Select::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('year')
                    ->numeric()
                    ->required()
                    ->minValue(2000)
                    ->maxValue(2200),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date')
                    ->required()
                    ->after('start_date'),
                Select::make('status')
                    ->options(FiscalYearStatus::class)
                    ->default(FiscalYearStatus::Open),
                Toggle::make('is_current')
                    ->default(false),
            ]);
    }
}
