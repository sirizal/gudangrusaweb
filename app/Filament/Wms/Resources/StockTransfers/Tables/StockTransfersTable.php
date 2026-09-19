<?php

namespace App\Filament\Wms\Resources\StockTransfers\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('transfer_date', 'desc')
            ->columns([
                TextColumn::make('transfer_code')->label('Transfer')->searchable()->sortable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->searchable(),
                TextColumn::make('fromLocation.location_code')->label('From'),
                TextColumn::make('toLocation.location_code')->label('To'),
                TextColumn::make('transfer_date')->date()->sortable(),
            ])
            ->actions([ViewAction::make()]);
    }
}
