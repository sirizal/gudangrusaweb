<?php

namespace App\Filament\Purchasing\Resources\VendorPayments;

use App\Filament\Purchasing\Resources\VendorPayments\Pages\CreateVendorPayment;
use App\Filament\Purchasing\Resources\VendorPayments\Pages\ListVendorPayments;
use App\Filament\Purchasing\Resources\VendorPayments\Pages\ViewVendorPayment;
use App\Filament\Purchasing\Resources\VendorPayments\Schemas\VendorPaymentForm;
use App\Filament\Purchasing\Resources\VendorPayments\Tables\VendorPaymentsTable;
use App\Models\VendorPayment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class VendorPaymentResource extends Resource
{
    protected static ?string $model = VendorPayment::class;

    protected static UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function form(Schema $schema): Schema
    {
        return VendorPaymentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorPaymentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorPayments::route('/'),
            'create' => CreateVendorPayment::route('/create'),
            'view' => ViewVendorPayment::route('/{record}'),
        ];
    }
}
