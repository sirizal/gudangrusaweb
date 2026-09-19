<?php

namespace App\Filament\Purchasing\Resources\VendorPayments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VendorPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('payment_date', 'desc')
            ->columns([
                TextColumn::make('payment_code')->label('Payment')->searchable()->sortable(),
                TextColumn::make('vendor.name')->label('Vendor')->searchable()->sortable(),
                TextColumn::make('payment_date')->date()->sortable(),
                TextColumn::make('reference')->searchable()->toggleable(),
                TextColumn::make('total')->numeric()->sortable(),
            ])
            ->actions([ViewAction::make()]);
    }
}
