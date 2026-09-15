<?php

namespace App\Filament\Geography\Resources\SubDistricts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SubDistrictForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('district_id')
                    ->label('District')
                    ->relationship('district', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->code.' - '.$record->name)
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(30)
                    ->helperText('Kemendagri sub-district code, e.g. 31.74.07.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
