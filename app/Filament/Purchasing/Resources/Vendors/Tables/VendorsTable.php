<?php

namespace App\Filament\Purchasing\Resources\Vendors\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vendor_code')->label('Code')->searchable()->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('phone')->toggleable(),
                IconColumn::make('is_pkp')->boolean()->label('PKP')->toggleable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                TernaryFilter::make('is_pkp')->label('PKP'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
