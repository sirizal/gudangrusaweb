<?php

namespace App\Filament\Accounting\Resources\Companies;

use App\Filament\Accounting\Resources\Companies\Pages\CreateCompany;
use App\Filament\Accounting\Resources\Companies\Pages\EditCompany;
use App\Filament\Accounting\Resources\Companies\Pages\ListCompanies;
use App\Filament\Accounting\Resources\Companies\RelationManagers\CompanyBoardMembersRelationManager;
use App\Filament\Accounting\Resources\Companies\RelationManagers\CompanyDocumentsRelationManager;
use App\Filament\Accounting\Resources\Companies\Schemas\CompanyForm;
use App\Filament\Accounting\Resources\Companies\Tables\CompaniesTable;
use App\Models\Company;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    public static function form(Schema $schema): Schema
    {
        return CompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CompanyDocumentsRelationManager::class,
            CompanyBoardMembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
