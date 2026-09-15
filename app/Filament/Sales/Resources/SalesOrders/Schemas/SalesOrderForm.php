<?php

namespace App\Filament\Sales\Resources\SalesOrders\Schemas;

use App\Enums\SalesOrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PaymentTerm;
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

class SalesOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Order')
                    ->columns(4)
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->relationship('company', 'name')
                            ->default(fn (): ?int => Company::query()->orderBy('id')->first()?->id)
                            ->preload()
                            ->searchable()
                            ->required()
                            ->disabledOn('edit'),
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->getOptionLabelFromRecordUsing(fn (Customer $record): string => $record->customer_code.' - '.$record->name)
                            ->preload()
                            ->searchable()
                            ->required()
                            ->live(),
                        Select::make('billing_address_id')
                            ->label('Billing address')
                            ->options(fn (Get $get): array => CustomerAddress::query()
                                ->when($get('customer_id'), fn ($query, $customerId) => $query->where('customer_id', $customerId))
                                ->orderBy('address_code')
                                ->pluck('address_code', 'id')
                                ->all())
                            ->searchable()
                            ->live(),
                        Select::make('shipping_address_id')
                            ->label('Shipping address')
                            ->options(fn (Get $get): array => CustomerAddress::query()
                                ->when($get('customer_id'), fn ($query, $customerId) => $query->where('customer_id', $customerId))
                                ->orderBy('address_code')
                                ->pluck('address_code', 'id')
                                ->all())
                            ->searchable()
                            ->live(),
                        DatePicker::make('order_date')
                            ->required()
                            ->default(now()),
                        Select::make('payment_term_id')
                            ->label('Payment term')
                            ->options(fn (Get $get): array => PaymentTerm::query()
                                ->when($get('customer_id'), function ($query, $customerId) {
                                    $termId = Customer::find($customerId)?->payment_term_id;

                                    return $termId ? $query->orWhere('id', $termId) : $query;
                                })
                                ->orderBy('code')
                                ->pluck('code', 'id')
                                ->all())
                            ->searchable(),
                        TextInput::make('order_code')
                            ->label('Order code')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Select::make('status')
                            ->options(SalesOrderStatus::class)
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn('edit'),
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
                Section::make('Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Order lines')
                            ->columns(5)
                            ->schema([
                                Select::make('product_id')
                                    ->label('Product')
                                    ->options(fn (): array => Product::query()
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(fn (Product $product): array => [$product->id => $product->name.' ('.$product->sku.')'])
                                        ->all())
                                    ->searchable()
                                    ->preload(),
                                Textarea::make('description')
                                    ->rows(1),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        $set('line_total', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2));
                                    }),
                                TextInput::make('unit_price')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set): void {
                                        $set('line_total', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2));
                                    }),
                                TextInput::make('line_total')
                                    ->label('Total')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->formatStateUsing(fn (Get $get): string => number_format(round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2), 0, ',', '.')),
                            ])
                            ->addActionLabel('Add line')
                            ->defaultItems(1)
                            ->minItems(1),
                    ]),
                Section::make('Totals')
                    ->columns(3)
                    ->schema([
                        TextInput::make('discount_amount')
                            ->label('Discount')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('Rp')
                            ->live(),
                        TextInput::make('subtotal')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function (Get $get): string {
                                $subtotal = 0;

                                foreach ($get('lines') ?? [] as $line) {
                                    $subtotal += (float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0);
                                }

                                return number_format(round($subtotal, 2), 0, ',', '.');
                            }),
                        TextInput::make('total')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function (Get $get): string {
                                $subtotal = 0;

                                foreach ($get('lines') ?? [] as $line) {
                                    $subtotal += (float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0);
                                }

                                return number_format(round($subtotal - (float) ($get('discount_amount') ?? 0), 2), 0, ',', '.');
                            }),
                    ]),
            ]);
    }
}
