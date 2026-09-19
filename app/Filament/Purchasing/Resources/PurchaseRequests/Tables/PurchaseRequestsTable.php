<?php

namespace App\Filament\Purchasing\Resources\PurchaseRequests\Tables;

use App\Enums\PurchaseRequestStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('request_date', 'desc')
            ->columns([
                TextColumn::make('request_code')->label('Request')->searchable()->sortable(),
                TextColumn::make('vendor.name')->label('Vendor')->placeholder('—')->searchable()->sortable(),
                TextColumn::make('request_date')->date()->sortable(),
                TextColumn::make('needed_date')->date()->placeholder('—')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('total')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PurchaseRequestStatus::class),
                SelectFilter::make('vendor_id')->label('Vendor')->relationship('vendor', 'name')->searchable()->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
