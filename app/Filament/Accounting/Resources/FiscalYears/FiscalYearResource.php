<?php

namespace App\Filament\Accounting\Resources\FiscalYears;

use App\Filament\Accounting\Resources\FiscalYears\Pages\CreateFiscalYear;
use App\Filament\Accounting\Resources\FiscalYears\Pages\EditFiscalYear;
use App\Filament\Accounting\Resources\FiscalYears\Pages\ListFiscalYears;
use App\Filament\Accounting\Resources\FiscalYears\RelationManagers\AccountingPeriodsRelationManager;
use App\Filament\Accounting\Resources\FiscalYears\Schemas\FiscalYearForm;
use App\Filament\Accounting\Resources\FiscalYears\Tables\FiscalYearsTable;
use App\Models\FiscalYear;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FiscalYearResource extends Resource
{
    protected static ?string $model = FiscalYear::class;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    public static function form(Schema $schema): Schema
    {
        return FiscalYearForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FiscalYearsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AccountingPeriodsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFiscalYears::route('/'),
            'create' => CreateFiscalYear::route('/create'),
            'edit' => EditFiscalYear::route('/{record}/edit'),
        ];
    }
}
