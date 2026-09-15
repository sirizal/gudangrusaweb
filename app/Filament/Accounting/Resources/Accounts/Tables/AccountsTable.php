<?php

namespace App\Filament\Accounting\Resources\Accounts\Tables;

use App\Enums\AccountType;
use App\Enums\NormalBalance;
use App\Models\Account;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('account_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (Account $record): string => str_repeat('——', max(0, $record->level - 1)).' '.$record->account_name),
                TextColumn::make('parent.account_code')
                    ->label('Parent')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('account_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('normal_balance')
                    ->badge()
                    ->color(fn (NormalBalance $state): string => $state->isDebit() ? 'info' : 'warning'),
                IconColumn::make('is_group')
                    ->boolean()
                    ->label('Group'),
                IconColumn::make('is_postable')
                    ->boolean()
                    ->label('Postable'),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->options(AccountType::class),
                SelectFilter::make('normal_balance')
                    ->options(NormalBalance::class),
                TernaryFilter::make('is_group')
                    ->label('Group'),
                TernaryFilter::make('is_postable')
                    ->label('Postable'),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('account_code');
    }
}
