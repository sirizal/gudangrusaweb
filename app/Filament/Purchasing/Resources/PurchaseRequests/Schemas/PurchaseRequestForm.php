<?php

namespace App\Filament\Purchasing\Resources\PurchaseRequests\Schemas;

use App\Enums\PurchaseRequestStatus;
use App\Enums\PurchaseType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PurchaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Purchase request')
                    ->columns(3)
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->relationship('company', 'name')
                            ->default(fn (): ?int => Company::query()->orderBy('id')->first()?->id)
                            ->preload()->searchable()->required()->disabledOn('edit'),
                        Select::make('vendor_id')
                            ->label('Vendor')
                            ->relationship('vendor', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->vendor_code.' - '.$record->name)
                            ->preload()->searchable(),
                        DatePicker::make('request_date')->required()->default(now()),
                        DatePicker::make('needed_date'),
                        TextInput::make('request_code')
                            ->label('Request code')
                            ->disabled()->dehydrated(false)->visibleOn('edit'),
                        Select::make('status')
                            ->options(PurchaseRequestStatus::class)
                            ->disabled()->dehydrated(false)->visibleOn('edit'),
                        Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),
                Section::make('Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Request lines')
                            ->columns(6)
                            ->schema([
                                Select::make('purchase_type')
                                    ->label('Type')
                                    ->options(PurchaseType::class)
                                    ->default('inventory')
                                    ->required()
                                    ->live(),
                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(fn (): array => Product::query()->orderBy('name')->get()->mapWithKeys(fn (Product $p): array => [$p->id => $p->name.' ('.$p->sku.')'])->all())
                                    ->searchable()->preload()->columnSpan(2)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                        $product = $state ? Product::find($state) : null;
                                        $price = (float) ($product?->price ?? 0);

                                        $set('unit_price', $price);
                                        $set('description', $product?->name);
                                        $set('line_total', round((float) ($get('quantity') ?? 0) * $price, 2));
                                    })
                                    ->visible(fn (Get $get): bool => ! self::isAccountType($get('purchase_type')))
                                    ->required(fn (Get $get): bool => ! self::isAccountType($get('purchase_type'))),
                                Select::make('account_id')
                                    ->label(fn (Get $get): string => self::isCapex($get('purchase_type')) ? 'Asset account' : 'Expense account')
                                    ->options(fn (Get $get): array => self::accountOptions($get('purchase_type')))
                                    ->searchable()->preload()->columnSpan(2)
                                    ->visible(fn (Get $get): bool => self::isAccountType($get('purchase_type')))
                                    ->required(fn (Get $get): bool => self::isAccountType($get('purchase_type'))),
                                TextInput::make('quantity')->numeric()->default(1)->minValue(1)->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('line_total', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2))),
                                TextInput::make('unit_price')->numeric()->default(0)->minValue(0)->prefix('Rp')->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('line_total', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2))),
                                TextInput::make('line_total')->label('Total')->disabled()->dehydrated(false)
                                    ->formatStateUsing(fn (Get $get): string => number_format(round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2), 0, ',', '.')),
                                Textarea::make('description')->rows(1)->columnSpanFull(),
                            ])
                            ->addActionLabel('Add line')
                            ->defaultItems(1)
                            ->minItems(1),
                    ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    public static function accountOptions(PurchaseType|string|null $purchaseType): array
    {
        $types = self::isCapex($purchaseType)
            ? ['asset']
            : ['expense', 'cost_of_sales', 'other_expense'];

        return Account::query()
            ->where('is_postable', true)
            ->where('is_group', false)
            ->where('is_active', true)
            ->whereIn('account_type', $types)
            ->orderBy('account_code')
            ->get()
            ->mapWithKeys(fn (Account $a): array => [$a->id => $a->account_code.' - '.$a->account_name])
            ->all();
    }

    /**
     * Whether the purchase type uses a GL account (general purchase or CAPEX).
     */
    public static function isAccountType(PurchaseType|string|null $purchaseType): bool
    {
        $value = $purchaseType instanceof PurchaseType ? $purchaseType->value : $purchaseType;

        return in_array($value, [PurchaseType::General->value, PurchaseType::Capex->value], true);
    }

    public static function isCapex(PurchaseType|string|null $purchaseType): bool
    {
        $value = $purchaseType instanceof PurchaseType ? $purchaseType->value : $purchaseType;

        return $value === PurchaseType::Capex->value;
    }
}
