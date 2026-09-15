<?php

namespace App\Filament\Accounting\Resources\OpeningBalances\Tables;

use App\Models\OpeningBalance;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OpeningBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fiscalYear.name')
                    ->label('Fiscal year'),
                TextColumn::make('account.account_code')
                    ->label('Code'),
                TextColumn::make('account.account_name')
                    ->label('Account'),
                TextColumn::make('debit')
                    ->money('IDR', divideBy: 100)
                    ->sortable(),
                TextColumn::make('credit')
                    ->money('IDR', divideBy: 100)
                    ->sortable(),
            ])
            ->defaultSort('fiscal_year_id')
            ->filters([
                SelectFilter::make('fiscal_year_id')
                    ->label('Fiscal year')
                    ->relationship('fiscalYear', 'name'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (OpeningBalance $record): bool => auth()->user()->can('update', $record)),
                DeleteAction::make()
                    ->visible(fn (OpeningBalance $record): bool => auth()->user()->can('delete', $record)),
            ]);
    }
}
