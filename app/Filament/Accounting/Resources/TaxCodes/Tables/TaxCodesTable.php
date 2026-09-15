<?php

namespace App\Filament\Accounting\Resources\TaxCodes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TaxCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tax_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('rate')
                    ->numeric(2)
                    ->suffix('%'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('tax_type')
                    ->options([
                        'PPN' => 'PPN',
                        'PPh 21' => 'PPh 21',
                        'PPh 22' => 'PPh 22',
                        'PPh 23' => 'PPh 23',
                        'PPh 4(2)' => 'PPh 4(2)',
                        'PPh 25' => 'PPh 25',
                        'PPh 29' => 'PPh 29',
                    ]),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
