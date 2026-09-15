<?php

namespace App\Filament\Accounting\Resources\FiscalYears\Tables;

use App\Enums\FiscalYearStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class FiscalYearsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('year')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (FiscalYearStatus $state): string => match ($state) {
                        FiscalYearStatus::Open => 'success',
                        FiscalYearStatus::Closed => 'gray',
                    }),
                IconColumn::make('is_current')
                    ->boolean()
                    ->label('Current'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(FiscalYearStatus::class),
                TernaryFilter::make('is_current')
                    ->label('Current'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('year', 'desc');
    }
}
