<?php

namespace App\Filament\Wms\Resources\InventoryStocks\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryStocksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')->label('Product')->searchable()->sortable(),
                TextColumn::make('product.sku')->label('SKU')->searchable()->toggleable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->searchable()->sortable(),
                TextColumn::make('location.location_code')->label('Location')->searchable(),
                TextColumn::make('quantity')->numeric()->sortable(),
                TextColumn::make('reserved_quantity')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')->label('Warehouse')->relationship('warehouse', 'name')->searchable()->preload(),
            ])
            ->actions([ViewAction::make()]);
    }
}
