<?php

namespace App\Filament\Wms\Resources\StockAdjustments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockAdjustmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('adjustment_date', 'desc')
            ->columns([
                TextColumn::make('adjustment_code')->label('Adjustment')->searchable()->sortable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->searchable(),
                TextColumn::make('location.location_code')->label('Location'),
                TextColumn::make('adjustment_date')->date()->sortable(),
                TextColumn::make('total_cost')->numeric()->label('Net value')->sortable(),
            ])
            ->actions([ViewAction::make()]);
    }
}
