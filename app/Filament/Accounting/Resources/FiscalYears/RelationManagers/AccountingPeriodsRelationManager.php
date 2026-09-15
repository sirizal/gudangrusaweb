<?php

namespace App\Filament\Accounting\Resources\FiscalYears\RelationManagers;

use App\Enums\PeriodStatus;
use App\Models\AccountingPeriod;
use App\Services\Accounting\PeriodService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use InvalidArgumentException;

class AccountingPeriodsRelationManager extends RelationManager
{
    protected static string $relationship = 'accountingPeriods';

    protected static ?string $title = 'Accounting Periods';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('period_number')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(12)
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('period_name')
                    ->required()
                    ->maxLength(255),
                DatePicker::make('start_date')
                    ->required(),
                DatePicker::make('end_date')
                    ->required(),
                Select::make('status')
                    ->options(PeriodStatus::class)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('period_name')
            ->columns([
                TextColumn::make('period_number')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('period_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PeriodStatus $state): string => match ($state) {
                        PeriodStatus::Open => 'success',
                        PeriodStatus::Closed => 'gray',
                    }),
            ])
            ->recordActions([
                Action::make('close')
                    ->label('Close')
                    ->requiresConfirmation()
                    ->visible(fn (AccountingPeriod $record): bool => $record->status === PeriodStatus::Open)
                    ->authorize(fn (AccountingPeriod $record): bool => auth()->user()->can('close', $record))
                    ->action(function (AccountingPeriod $record): void {
                        try {
                            app(PeriodService::class)->close($record);
                            Notification::make()->title('Period closed')->success()->send();
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reopen')
                    ->label('Reopen')
                    ->requiresConfirmation()
                    ->visible(fn (AccountingPeriod $record): bool => $record->status === PeriodStatus::Closed)
                    ->authorize(fn (AccountingPeriod $record): bool => auth()->user()->can('reopen', $record))
                    ->action(function (AccountingPeriod $record): void {
                        app(PeriodService::class)->reopen($record);
                        Notification::make()->title('Period reopened')->success()->send();
                    }),
                EditAction::make()
                    ->modalWidth(Width::Large),
            ]);
    }
}
