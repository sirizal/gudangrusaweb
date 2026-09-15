<?php

namespace App\Filament\Accounting\Resources\Budgets;

use App\Filament\Accounting\Resources\Budgets\Pages\CreateBudget;
use App\Filament\Accounting\Resources\Budgets\Pages\EditBudget;
use App\Filament\Accounting\Resources\Budgets\Pages\ListBudgets;
use App\Filament\Accounting\Resources\Budgets\Pages\ViewBudget;
use App\Filament\Accounting\Resources\Budgets\Schemas\BudgetForm;
use App\Filament\Accounting\Resources\Budgets\Tables\BudgetsTable;
use App\Models\Budget;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static UnitEnum|string|null $navigationGroup = 'Budgeting';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    public static function form(Schema $schema): Schema
    {
        return BudgetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BudgetsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withSum('lines', 'annual_amount');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBudgets::route('/'),
            'create' => CreateBudget::route('/create'),
            'edit' => EditBudget::route('/{record}/edit'),
            'view' => ViewBudget::route('/{record}'),
        ];
    }
}
