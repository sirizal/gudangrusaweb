<?php

namespace App\Filament\Sales\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Invoice')
                    ->columns(3)
                    ->schema([
                        TextInput::make('invoice_code')
                            ->label('Invoice code'),
                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->disabled(),
                        TextInput::make('sales_order_id')
                            ->label('Sales order')
                            ->formatStateUsing(fn ($record): ?string => $record?->salesOrder?->order_code),
                        DatePicker::make('invoice_date')
                            ->label('Created'),
                        DatePicker::make('due_date')
                            ->label('Due'),
                        Select::make('status')
                            ->options(InvoiceStatus::class)
                            ->disabled(),
                        TextInput::make('subtotal')
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('discount_amount')
                            ->label('Discount')
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('tax_amount')
                            ->label('Tax')
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('total')
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('paid_amount')
                            ->numeric()
                            ->prefix('Rp'),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
