<?php

namespace App\Filament\Wms\Resources\StockMovements\Tables;

use App\Enums\StockMovementType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('movement_date', 'desc')
            ->columns([
                TextColumn::make('movement_date')->date()->sortable(),
                TextColumn::make('product.name')->label('Product')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->toggleable(),
                TextColumn::make('location.location_code')->label('Location')->toggleable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('quantity')->numeric()->sortable(),
                TextColumn::make('total_cost')->numeric()->label('Value')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(StockMovementType::class),
                SelectFilter::make('warehouse_id')->label('Warehouse')->relationship('warehouse', 'name')->searchable()->preload(),
            ]);
    }
}
