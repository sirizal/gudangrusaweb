<?php

namespace App\Filament\Accounting\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Recorded at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System')
                    ->sortable(),
                TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_ends_with($state, 'created') => 'success',
                        str_ends_with($state, 'deleted') => 'danger',
                        str_ends_with($state, 'closed'), str_ends_with($state, 'reversed') => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label('Record')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('auditable_id')
                    ->label('ID'),
                TextColumn::make('ip_address')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('action')
                    ->label('Action type')
                    ->options(fn (): array => collect(AuditLog::query()->distinct()->pluck('action'))
                        ->mapWithKeys(fn (string $action): array => [$action => $action])
                        ->all()),
                TernaryFilter::make('user_id')
                    ->label('Actor')
                    ->nullable(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn (): bool => auth()->user()->can('viewAny', AuditLog::class)),
            ]);
    }
}
