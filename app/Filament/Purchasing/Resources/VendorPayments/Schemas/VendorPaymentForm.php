<?php

namespace App\Filament\Purchasing\Resources\VendorPayments\Schemas;

use App\Models\Company;
use App\Models\VendorBill;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class VendorPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Vendor payment')
                    ->columns(3)
                    ->schema([
                        Select::make('company_id')->label('Company')->relationship('company', 'name')
                            ->default(fn (): ?int => Company::query()->orderBy('id')->first()?->id)
                            ->preload()->searchable()->required(),
                        Select::make('vendor_id')->label('Vendor')->relationship('vendor', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => $record->vendor_code.' - '.$record->name)
                            ->preload()->searchable()->required()->live(),
                        DatePicker::make('payment_date')->required()->default(now()),
                        TextInput::make('reference')->maxLength(100),
                        Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),
                Section::make('Allocations')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Bills to pay')
                            ->columns(3)
                            ->schema([
                                Select::make('vendor_bill_id')
                                    ->label('Vendor bill')
                                    ->options(function (Get $get): array {
                                        if (! $get('../../vendor_id')) {
                                            return [];
                                        }

                                        return VendorBill::query()
                                            ->where('vendor_id', $get('../../vendor_id'))
                                            ->whereNotIn('status', ['draft', 'cancelled', 'paid'])
                                            ->orderBy('bill_code')
                                            ->get()
                                            ->mapWithKeys(fn (VendorBill $bill): array => [$bill->id => $bill->bill_code.' (due '.$bill->due_date->toDateString().', outstanding '.number_format((float) $bill->total - (float) $bill->paid_amount, 0, ',', '.').')'])
                                            ->all();
                                    })
                                    ->searchable()->preload()->required()->live(),
                                TextInput::make('amount')->numeric()->required()->minValue(0.01)->prefix('Rp'),
                                TextInput::make('outstanding')
                                    ->label('Outstanding')
                                    ->disabled()->dehydrated(false)
                                    ->formatStateUsing(function (Get $get): string {
                                        $bill = VendorBill::find($get('vendor_bill_id'));

                                        return $bill ? number_format((float) $bill->total - (float) $bill->paid_amount, 0, ',', '.') : '—';
                                    }),
                            ])
                            ->addActionLabel('Add bill')
                            ->defaultItems(1)
                            ->minItems(1),
                    ]),
            ]);
    }
}
