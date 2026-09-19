<?php

namespace App\Filament\Wms\Resources\Warehouses\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse_code')->label('Code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('village.name')->label('City')->placeholder('—')->toggleable(),
                TextColumn::make('locations_count')->label('Locations')->counts('locations')->alignCenter(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([TernaryFilter::make('is_active')->label('Active')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
