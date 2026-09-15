<?php

namespace App\Filament\Accounting\Resources\Budgets\Tables;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Services\Accounting\BudgetExportService;
use Filament\Actions\Action as RowAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BudgetsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('fiscal_year_id', 'desc')
            ->columns([
                TextColumn::make('budget_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('budget_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fiscalYear.name')
                    ->label('Fiscal year')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('version')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('lines_sum_annual_amount')
                    ->label('Annual total')
                    ->sortable()
                    ->state(fn (Budget $record): string => number_format((float) $record->lines_sum_annual_amount, 0, ',', '.')),
                TextColumn::make('approved_at')
                    ->label('Approved')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('fiscal_year_id')
                    ->label('Fiscal year')
                    ->relationship('fiscalYear', 'name'),
                SelectFilter::make('status')
                    ->options(BudgetStatus::class),
            ])
            ->actions([
                RowAction::make('export-csv')
                    ->label('CSV')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn (Budget $record): StreamedResponse => app(BudgetExportService::class)->csv($record)),
                RowAction::make('export-xlsx')
                    ->label('Excel')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn (Budget $record): StreamedResponse => app(BudgetExportService::class)->xlsx($record)),
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
