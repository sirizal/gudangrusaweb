<?php

namespace App\Filament\Wms\Resources\OutboundShipments\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OutboundShipmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('shipment_date', 'desc')
            ->columns([
                TextColumn::make('shipment_code')->label('Shipment')->searchable()->sortable(),
                TextColumn::make('salesOrder.order_code')->label('Sales order')->searchable(),
                TextColumn::make('warehouse.name')->label('Warehouse')->searchable(),
                TextColumn::make('location.location_code')->label('Location'),
                TextColumn::make('shipment_date')->date()->sortable(),
                TextColumn::make('total_cost')->numeric()->label('COGS')->sortable(),
            ])
            ->actions([ViewAction::make()]);
    }
}
