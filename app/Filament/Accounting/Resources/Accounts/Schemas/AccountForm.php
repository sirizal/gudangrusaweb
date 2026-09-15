<?php

namespace App\Filament\Accounting\Resources\Accounts\Schemas;

use App\Enums\AccountType;
use App\Enums\CashFlowActivity;
use App\Enums\NormalBalance;
use App\Models\Account;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                Select::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->preload()
                    ->searchable()
                    ->required(),
                Select::make('parent_id')
                    ->label('Parent')
                    ->relationship(
                        'parent',
                        'account_code',
                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_group', true)->where('is_active', true),
                    )
                    ->getOptionLabelFromRecordUsing(fn (Account $record): string => $record->account_code.' - '.$record->account_name)
                    ->searchable()
                    ->preload()
                    ->helperText('Only group accounts can be parents.')
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                        if ($state && blank($get('level'))) {
                            $parent = Account::find($state);
                            if ($parent) {
                                $set('level', $parent->level + 1);
                            }
                        }
                    }),
                TextInput::make('account_code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(20),
                TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('account_name_en')
                    ->maxLength(255),
                Select::make('account_type')
                    ->options(AccountType::class)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                        if ($state && blank($get('normal_balance'))) {
                            $set('normal_balance', AccountType::tryFrom($state)?->defaultNormalBalance());
                        }
                    }),
                TextInput::make('account_sub_type')
                    ->maxLength(255),
                Select::make('normal_balance')
                    ->options(NormalBalance::class)
                    ->required(),
                TextInput::make('level')
                    ->numeric()
                    ->default(1)
                    ->minValue(1),
                Toggle::make('is_group')
                    ->default(false)
                    ->live()
                    ->afterStateUpdated(fn (Set $set, ?bool $state): mixed => $set('is_postable', ! $state)),
                Toggle::make('is_postable')
                    ->default(true),
                Toggle::make('is_active')
                    ->default(true),
                Select::make('financial_statement')
                    ->options([
                        'income_statement' => 'Income Statement',
                        'balance_sheet' => 'Balance Sheet',
                        'cash_flow' => 'Cash Flow',
                    ]),
                TextInput::make('financial_statement_line')
                    ->maxLength(255),
                TextInput::make('cash_flow_category')
                    ->maxLength(255),
                Select::make('cash_flow_activity')
                    ->options(CashFlowActivity::class),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
