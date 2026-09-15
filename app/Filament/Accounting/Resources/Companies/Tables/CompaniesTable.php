<?php

namespace App\Filament\Accounting\Resources\Companies\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompaniesTable
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
                TextColumn::make('village.name')
                    ->label('Address')
                    ->formatStateUsing(fn ($record): string => $record->address ?: ($record->village?->name ?? '—'))
                    ->placeholder('—')
                    ->toggleable()
                    ->limit(30),
                TextColumn::make('npwp')
                    ->searchable()
                    ->toggleable(),
                IconColumn::make('is_pkp')
                    ->boolean()
                    ->label('PKP')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
