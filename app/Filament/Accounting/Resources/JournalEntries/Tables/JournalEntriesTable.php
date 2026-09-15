<?php

namespace App\Filament\Accounting\Resources\JournalEntries\Tables;

use App\Enums\JournalStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class JournalEntriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('journal_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('journal_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (JournalStatus $state): string => match ($state) {
                        JournalStatus::Draft => 'gray',
                        JournalStatus::Submitted => 'warning',
                        JournalStatus::Approved => 'info',
                        JournalStatus::Posted => 'success',
                        JournalStatus::Reversed => 'danger',
                        JournalStatus::Cancelled => 'gray',
                    })
                    ->formatStateUsing(fn (JournalStatus $state): string => $state->getLabel()),
                TextColumn::make('accountingPeriod.fiscalYear.name')
                    ->label('Fiscal year'),
                TextColumn::make('accountingPeriod.period_number')
                    ->label('Period')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('createdBy.name')
                    ->label('Created by')
                    ->placeholder('—'),
                TextColumn::make('posted_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('journal_date', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(JournalStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn (JournalEntry $record): bool => auth()->user()->can('view', $record)),
                EditAction::make()
                    ->visible(fn (JournalEntry $record): bool => auth()->user()->can('update', $record)),
                DeleteAction::make()
                    ->visible(fn (JournalEntry $record): bool => auth()->user()->can('delete', $record)),
            ]);
    }
}
