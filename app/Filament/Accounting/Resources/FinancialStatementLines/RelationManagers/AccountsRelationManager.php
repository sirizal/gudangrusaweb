<?php

namespace App\Filament\Accounting\Resources\FinancialStatementLines\RelationManagers;

use App\Models\FinancialStatementLine;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'accounts';

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('account_code')
            ->columns([
                TextColumn::make('account_code')
                    ->label('Code'),
                TextColumn::make('account_name')
                    ->label('Name'),
                TextColumn::make('account_type')
                    ->label('Type')
                    ->badge(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                DetachAction::make(),
            ]);
    }

    public static function canViewForRecord(mixed $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof FinancialStatementLine
            && auth()->user()?->can('view', $ownerRecord) === true;
    }
}
