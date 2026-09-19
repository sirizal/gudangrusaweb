<?php

namespace App\Filament\Purchasing\Resources\VendorBills\Schemas;

use App\Enums\PurchaseType;
use App\Enums\VendorBillStatus;
use App\Filament\Purchasing\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
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

class VendorBillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Vendor bill')
                    ->columns(3)
                    ->schema([
                        Select::make('company_id')->label('Company')->relationship('company', 'name')
                            ->default(fn (): ?int => Company::query()->orderBy('id')->first()?->id)
                            ->preload()->searchable()->required()->disabledOn('edit'),
                        Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->vendor_code.' - '.$record->name)
                            ->preload()->searchable()->required(),
                        Select::make('purchase_order_id')->label('Purchase order')
                            ->relationship('purchaseOrder', 'po_code')->preload()->searchable(),
                        DatePicker::make('bill_date')->required()->default(now()),
                        DatePicker::make('due_date')->required()->default(now()->addDays(30)),
                        Select::make('payment_term_id')->label('Payment term')
                            ->relationship('paymentTerm', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->code.' - '.$record->name)
                            ->preload()->searchable(),
                        TextInput::make('bill_code')->label('Bill code')->disabled()->dehydrated(false)->visibleOn('edit'),
                        Select::make('status')->options(VendorBillStatus::class)->disabled()->dehydrated(false)->visibleOn('edit'),
                        Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),
                Section::make('Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Bill lines')
                            ->columns(6)
                            ->schema([
                                Select::make('purchase_type')->label('Type')->options(PurchaseType::class)->default('inventory')->required()->live(),
                                Select::make('product_id')->label('Product')
                                    ->options(fn (): array => Product::query()->orderBy('name')->get()->mapWithKeys(fn (Product $p): array => [$p->id => $p->name.' ('.$p->sku.')'])->all())
                                    ->searchable()->preload()->columnSpan(2)
                                    ->visible(fn (Get $get): bool => ! PurchaseRequestForm::isAccountType($get('purchase_type'))),
                                Select::make('account_id')
                                    ->label(fn (Get $get): string => PurchaseRequestForm::isCapex($get('purchase_type')) ? 'Asset account' : 'Expense account')
                                    ->options(fn (Get $get): array => PurchaseRequestForm::accountOptions($get('purchase_type')))
                                    ->searchable()->preload()->columnSpan(2)
                                    ->visible(fn (Get $get): bool => PurchaseRequestForm::isAccountType($get('purchase_type'))),
                                TextInput::make('quantity')->numeric()->default(1)->minValue(1)->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('line_total', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2))),
                                TextInput::make('unit_price')->numeric()->default(0)->minValue(0)->prefix('Rp')->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => $set('line_total', round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2))),
                                TextInput::make('line_total')->label('Total')->disabled()->dehydrated(false)
                                    ->formatStateUsing(fn (Get $get): string => number_format(round((float) ($get('quantity') ?? 0) * (float) ($get('unit_price') ?? 0), 2), 0, ',', '.')),
                                Textarea::make('description')->rows(1)->columnSpanFull(),
                            ])
                            ->addActionLabel('Add line')->defaultItems(1)->minItems(1),
                    ]),
            ]);
    }
}
