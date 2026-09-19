<?php

namespace App\Filament\Purchasing\Resources\VendorBills\Tables;

use App\Enums\VendorBillStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VendorBillsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('bill_date', 'desc')
            ->columns([
                TextColumn::make('bill_code')->label('Bill')->searchable()->sortable(),
                TextColumn::make('vendor.name')->label('Vendor')->searchable()->sortable(),
                TextColumn::make('bill_date')->date()->sortable(),
                TextColumn::make('due_date')->date()->sortable(),
                TextColumn::make('total')->numeric()->sortable(),
                TextColumn::make('paid_amount')->numeric()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(VendorBillStatus::class),
                SelectFilter::make('vendor_id')->label('Vendor')->relationship('vendor', 'name')->searchable()->preload(),
            ])
            ->actions([ViewAction::make(), EditAction::make()]);
    }
}
