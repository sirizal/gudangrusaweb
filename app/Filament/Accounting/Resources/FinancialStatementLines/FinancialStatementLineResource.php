<?php

namespace App\Filament\Accounting\Resources\FinancialStatementLines;

use App\Filament\Accounting\Resources\FinancialStatementLines\Pages\CreateFinancialStatementLine;
use App\Filament\Accounting\Resources\FinancialStatementLines\Pages\EditFinancialStatementLine;
use App\Filament\Accounting\Resources\FinancialStatementLines\Pages\ListFinancialStatementLines;
use App\Filament\Accounting\Resources\FinancialStatementLines\RelationManagers\AccountsRelationManager;
use App\Filament\Accounting\Resources\FinancialStatementLines\Schemas\FinancialStatementLineForm;
use App\Filament\Accounting\Resources\FinancialStatementLines\Tables\FinancialStatementLinesTable;
use App\Models\FinancialStatementLine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FinancialStatementLineResource extends Resource
{
    protected static ?string $model = FinancialStatementLine::class;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    public static function form(Schema $schema): Schema
    {
        return FinancialStatementLineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FinancialStatementLinesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AccountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancialStatementLines::route('/'),
            'create' => CreateFinancialStatementLine::route('/create'),
            'edit' => EditFinancialStatementLine::route('/{record}/edit'),
        ];
    }
}
