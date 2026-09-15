<?php

namespace App\Filament\Accounting\Resources\JournalEntries\Schemas;

use App\Models\Account;
use App\Models\AccountingPeriod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class JournalEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Journal details')
                    ->columns(4)
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->relationship('company', 'name')
                            ->preload()
                            ->searchable()
                            ->required(),
                        Select::make('accounting_period_id')
                            ->label('Period')
                            ->relationship('accountingPeriod', 'period_number')
                            ->getOptionLabelFromRecordUsing(fn (AccountingPeriod $record): string => $record->fiscalYear->name.' - Periode '.$record->period_number)
                            ->preload()
                            ->searchable()
                            ->required(),
                        DatePicker::make('journal_date')
                            ->required()
                            ->default(now()),
                        TextInput::make('journal_number')
                            ->disabled()
                            ->visibleOn('edit'),
                        Textarea::make('description')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
                Section::make('Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Journal lines')
                            ->relationship()
                            ->columns(4)
                            ->schema([
                                Select::make('account_id')
                                    ->label('Account')
                                    ->options(fn (): array => Account::query()
                                        ->where('is_postable', true)
                                        ->where('is_group', false)
                                        ->get()
                                        ->mapWithKeys(fn (Account $account): array => [$account->id => $account->account_code.' - '.$account->account_name])
                                        ->all())
                                    ->searchable()
                                    ->required(),
                                Textarea::make('description')
                                    ->rows(2),
                                TextInput::make('debit')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('credit')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                Select::make('cost_center_id')
                                    ->label('Cost center')
                                    ->relationship('costCenter', 'name')
                                    ->preload()
                                    ->searchable(),
                                Select::make('department_id')
                                    ->label('Department')
                                    ->relationship('department', 'name')
                                    ->preload()
                                    ->searchable(),
                                Select::make('project_id')
                                    ->label('Project')
                                    ->relationship('project', 'name')
                                    ->preload()
                                    ->searchable(),
                                Select::make('tax_code_id')
                                    ->label('Tax code')
                                    ->relationship('taxCode', 'code')
                                    ->preload()
                                    ->searchable(),
                            ])
                            ->addActionLabel('Add line')
                            ->defaultItems(2)
                            ->minItems(1),
                    ]),
            ]);
    }
}
