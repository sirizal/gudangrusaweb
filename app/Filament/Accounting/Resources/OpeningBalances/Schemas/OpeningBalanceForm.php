<?php

namespace App\Filament\Accounting\Resources\OpeningBalances\Schemas;

use App\Models\Account;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OpeningBalanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                Select::make('fiscal_year_id')
                    ->label('Fiscal year')
                    ->relationship('fiscalYear', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
                Select::make('account_id')
                    ->label('Account')
                    ->options(fn (): array => Account::query()
                        ->where('is_postable', true)
                        ->where('is_group', false)
                        ->whereIn('account_type', ['asset', 'liability', 'equity'])
                        ->get()
                        ->mapWithKeys(fn (Account $account): array => [$account->id => $account->account_code.' - '.$account->account_name])
                        ->all())
                    ->searchable()
                    ->required(),
                TextInput::make('debit')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                TextInput::make('credit')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
            ]);
    }
}
