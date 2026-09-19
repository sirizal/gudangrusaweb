<?php

namespace App\Filament\Purchasing\Resources\PurchaseOrders\Tables;

use App\Enums\PurchaseOrderStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('po_date', 'desc')
            ->columns([
                TextColumn::make('po_code')->label('PO')->searchable()->sortable(),
                TextColumn::make('vendor.name')->label('Vendor')->searchable()->sortable(),
                TextColumn::make('po_date')->date()->sortable(),
                TextColumn::make('expected_date')->date()->placeholder('—')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('total')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PurchaseOrderStatus::class),
                SelectFilter::make('vendor_id')->label('Vendor')->relationship('vendor', 'name')->searchable()->preload(),
            ])
            ->actions([ViewAction::make(), EditAction::make()]);
    }
}
