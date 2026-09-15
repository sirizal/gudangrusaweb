<?php

namespace App\Filament\Accounting\Resources\Budgets\Schemas;

use App\Enums\BudgetStatus;
use App\Models\Account;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Project;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class BudgetForm
{
    /**
     * @var array<int, string>
     */
    private const MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Budget')
                    ->columns(4)
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->relationship('company', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->disabledOn('edit'),
                        Select::make('fiscal_year_id')
                            ->label('Fiscal year')
                            ->relationship('fiscalYear', 'name')
                            ->preload()
                            ->searchable()
                            ->required()
                            ->disabledOn('edit'),
                        TextInput::make('budget_code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(30)
                            ->disabledOn('edit'),
                        TextInput::make('budget_name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('version')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Select::make('status')
                            ->options(BudgetStatus::class)
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Textarea::make('description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Budget lines')
                            ->columns(4)
                            ->schema(self::lineSchema())
                            ->addActionLabel('Add line')
                            ->defaultItems(1)
                            ->minItems(1),
                    ]),
            ]);
    }

    /**
     * @return array<int, mixed>
     */
    private static function lineSchema(): array
    {
        $fields = [
            Select::make('account_id')
                ->label('Account')
                ->options(fn (): array => Account::query()
                    ->where('is_postable', true)
                    ->where('is_group', false)
                    ->where('is_active', true)
                    ->get()
                    ->mapWithKeys(fn (Account $account): array => [$account->id => $account->account_code.' - '.$account->account_name])
                    ->all())
                ->searchable()
                ->required(),
            Select::make('cost_center_id')
                ->label('Cost center')
                ->options(fn (): array => CostCenter::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable(),
            Select::make('department_id')
                ->label('Department')
                ->options(fn (): array => Department::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable(),
            Select::make('project_id')
                ->label('Project')
                ->options(fn (): array => Project::query()
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable(),
            Textarea::make('description')
                ->rows(2)
                ->columnSpanFull(),
        ];

        foreach (range(1, 12) as $month) {
            $fields[] = TextInput::make('month_'.$month)
                ->label(self::MONTH_LABELS[$month - 1])
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->live(onBlur: true);
        }

        $fields[] = TextInput::make('total')
            ->label('Total')
            ->disabled()
            ->dehydrated(false)
            ->formatStateUsing(function (Get $get): string {
                $total = 0;

                foreach (range(1, 12) as $month) {
                    $total += (float) ($get('month_'.$month) ?? 0);
                }

                return number_format($total, 0, ',', '.');
            });

        return $fields;
    }
}
