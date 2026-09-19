<?php

namespace App\Filament\Purchasing\Resources\GoodsReceipts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GoodsReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Goods receipt')
                    ->columns(3)
                    ->schema([
                        TextInput::make('receipt_code')->label('Receipt code'),
                        TextInput::make('purchase_order_id')
                            ->label('Purchase order')
                            ->formatStateUsing(fn ($record): ?string => $record?->purchaseOrder?->po_code),
                        DatePicker::make('receipt_date')->label('Receipt date'),
                        TextInput::make('total')->numeric()->prefix('Rp'),
                        TextInput::make('status'),
                    ]),
            ]);
    }
}
