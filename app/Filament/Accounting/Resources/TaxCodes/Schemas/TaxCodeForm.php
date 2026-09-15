<?php

namespace App\Filament\Accounting\Resources\TaxCodes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaxCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('tax_type')
                    ->options([
                        'PPN' => 'PPN',
                        'PPh 21' => 'PPh 21',
                        'PPh 22' => 'PPh 22',
                        'PPh 23' => 'PPh 23',
                        'PPh 4(2)' => 'PPh 4(2)',
                        'PPh 25' => 'PPh 25',
                        'PPh 29' => 'PPh 29',
                    ])
                    ->required(),
                TextInput::make('rate')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(100)
                    ->suffix('%'),
                Select::make('account_id')
                    ->label('Expense account')
                    ->relationship('account', 'account_code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->account_code.' - '.$record->account_name)
                    ->searchable()
                    ->preload(),
                Select::make('payable_account_id')
                    ->label('Payable account')
                    ->relationship('payableAccount', 'account_code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->account_code.' - '.$record->account_name)
                    ->searchable()
                    ->preload(),
                Select::make('receivable_account_id')
                    ->label('Receivable account')
                    ->relationship('receivableAccount', 'account_code')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->account_code.' - '.$record->account_name)
                    ->searchable()
                    ->preload(),
                Toggle::make('is_active')
                    ->default(true),
            ]);
    }
}
