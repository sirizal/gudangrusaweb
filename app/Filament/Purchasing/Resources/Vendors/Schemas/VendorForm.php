<?php

namespace App\Filament\Purchasing\Resources\Vendors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VendorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('vendor_code')
                    ->label('Vendor code')
                    ->disabled()
                    ->dehydrated(false)
                    ->visibleOn('edit')
                    ->helperText('Auto-generated on creation.'),
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('email')->email()->maxLength(100),
                TextInput::make('phone')->maxLength(30),
                TextInput::make('website')->url()->maxLength(100),
                TextInput::make('npwp')
                    ->label('NPWP')
                    ->regex('/^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/')
                    ->placeholder('00.000.000.0-000.000')
                    ->maxLength(20),
                Toggle::make('is_pkp')->label('PKP (VAT registered)'),
                Select::make('payment_term_id')
                    ->label('Payment term')
                    ->relationship('paymentTerm', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => $record->code.' - '.$record->name)
                    ->preload()
                    ->searchable(),
                Toggle::make('is_active')->default(true),
            ]);
    }
}
