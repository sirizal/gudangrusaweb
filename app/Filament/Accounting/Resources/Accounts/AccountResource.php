<?php

namespace App\Filament\Accounting\Resources\Accounts;

use App\Filament\Accounting\Resources\Accounts\Pages\CreateAccount;
use App\Filament\Accounting\Resources\Accounts\Pages\EditAccount;
use App\Filament\Accounting\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Accounting\Resources\Accounts\Schemas\AccountForm;
use App\Filament\Accounting\Resources\Accounts\Tables\AccountsTable;
use App\Models\Account;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    public static function form(Schema $schema): Schema
    {
        return AccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'create' => CreateAccount::route('/create'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }
}
