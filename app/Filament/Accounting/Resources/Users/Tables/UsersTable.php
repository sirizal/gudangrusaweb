<?php

namespace App\Filament\Accounting\Resources\Users\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->separator(','),
                TextColumn::make('created_at')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->label('Created'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make()
                    ->visible(fn ($record): bool => auth()->user()->can('update', $record)),
                DeleteAction::make()
                    ->visible(fn ($record): bool => auth()->user()->can('delete', $record))
                    ->disabled(fn ($record): bool => auth()->id() === $record->id),
            ]);
    }
}
