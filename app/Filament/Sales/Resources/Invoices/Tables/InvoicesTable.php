<?php

namespace App\Filament\Sales\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('invoice_date', 'desc')
            ->columns([
                TextColumn::make('invoice_code')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.customer_code')
                    ->label('Customer code')
                    ->toggleable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice_date')
                    ->label('Created')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Due')
                    ->date()
                    ->sortable(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('paid_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('overdue_days')
                    ->label('Overdue (days)')
                    ->numeric()
                    ->sortable()
                    ->color(fn (Invoice $record): string => $record->overdue_days > 0 ? 'danger' : 'gray'),
                TextColumn::make('aging_bucket')
                    ->label('Aging')
                    ->badge()
                    ->color('danger'),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class),
                Filter::make('overdue')
                    ->label('Overdue')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereColumn('paid_amount', '<', 'total')
                        ->whereDate('due_date', '<', now()->toDateString())),
                TernaryFilter::make('paid')
                    ->label('Paid')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereColumn('paid_amount', '>=', 'total'),
                        false: fn (Builder $query): Builder => $query->whereColumn('paid_amount', '<', 'total'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }
}
