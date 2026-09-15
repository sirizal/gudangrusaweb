<?php

namespace App\Filament\Geography\Resources\Villages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VillageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('sub_district_id')
                    ->label('Sub district')
                    ->relationship('subDistrict', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->code.' - '.$record->name)
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(40)
                    ->helperText('Kemendagri village code, e.g. 31.74.07.1001.'),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('postal_code')
                    ->label('Postal code')
                    ->required()
                    ->regex('/^\d{5}$/')
                    ->maxLength(5)
                    ->helperText('Indonesian 5-digit postal code.'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
