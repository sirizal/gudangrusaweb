<?php

namespace App\Filament\Purchasing\Resources\GoodsReceipts\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GoodsReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('receipt_date', 'desc')
            ->columns([
                TextColumn::make('receipt_code')->label('Receipt')->searchable()->sortable(),
                TextColumn::make('purchaseOrder.po_code')->label('PO')->searchable(),
                TextColumn::make('purchaseOrder.vendor.name')->label('Vendor')->searchable(),
                TextColumn::make('receipt_date')->date()->sortable(),
                TextColumn::make('total')->numeric()->sortable(),
            ])
            ->actions([ViewAction::make()]);
    }
}
