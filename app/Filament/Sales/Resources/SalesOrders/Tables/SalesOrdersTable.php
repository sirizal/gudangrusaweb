<?php

namespace App\Filament\Sales\Resources\SalesOrders\Tables;

use App\Enums\SalesOrderStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('order_date', 'desc')
            ->columns([
                TextColumn::make('order_code')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.customer_code')
                    ->label('Customer code')
                    ->toggleable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(SalesOrderStatus::class),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
