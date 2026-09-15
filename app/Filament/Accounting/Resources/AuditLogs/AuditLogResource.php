<?php

namespace App\Filament\Accounting\Resources\AuditLogs;

use App\Filament\Accounting\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Accounting\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Accounting\Resources\AuditLogs\Tables\AuditLogsTable;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static UnitEnum|string|null $navigationGroup = 'Controls';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?string $slug = 'audit-logs';

    public static function table(Table $table): Table
    {
        return AuditLogsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('user.name')
                    ->label('User')
                    ->placeholder('System'),
                TextEntry::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_ends_with($state, 'created') => 'success',
                        str_ends_with($state, 'deleted') => 'danger',
                        str_ends_with($state, 'closed'), str_ends_with($state, 'reversed') => 'warning',
                        default => 'gray',
                    }),
                TextEntry::make('auditable_type')
                    ->label('Record type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextEntry::make('auditable_id')
                    ->label('Record ID'),
                TextEntry::make('ip_address')
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->label('Recorded at'),
                TextEntry::make('changes')
                    ->label('Changes')
                    ->columnSpanFull()
                    ->state(fn (AuditLog $record): string => $record->changes !== null
                        ? json_encode($record->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                        : '(no tracked changes)')
                    ->size(TextSize::Small),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
