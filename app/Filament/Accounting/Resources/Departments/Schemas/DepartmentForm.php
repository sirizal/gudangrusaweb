<?php

namespace App\Filament\Accounting\Resources\Departments\Schemas;

use App\Models\Department;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DepartmentForm
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
                Select::make('parent_id')
                    ->label('Parent')
                    ->relationship('parent', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Department $record): string => $record->code.' - '.$record->name)
                    ->searchable()
                    ->preload()
                    ->placeholder('None'),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
