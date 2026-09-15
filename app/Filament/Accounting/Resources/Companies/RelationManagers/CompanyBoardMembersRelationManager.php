<?php

namespace App\Filament\Accounting\Resources\Companies\RelationManagers;

use App\Enums\CompanyBoardPosition;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyBoardMembersRelationManager extends RelationManager
{
    protected static string $relationship = 'boardMembers';

    protected static ?string $title = 'Board of Directors';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('position')
                    ->options(CompanyBoardPosition::class)
                    ->required(),
                TextInput::make('nik')
                    ->label('Personal ID (NIK)')
                    ->required()
                    ->regex('/^\d{16}$/')
                    ->maxLength(16),
                DatePicker::make('start_date')
                    ->label('Start date')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('End date')
                    ->after('start_date'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->headerActions([
                CreateAction::make(),
            ])
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('position')
                    ->badge()
                    ->sortable(),
                TextColumn::make('nik')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
