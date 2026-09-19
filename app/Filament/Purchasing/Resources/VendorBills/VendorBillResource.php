<?php

namespace App\Filament\Purchasing\Resources\VendorBills;

use App\Filament\Purchasing\Resources\VendorBills\Pages\CreateVendorBill;
use App\Filament\Purchasing\Resources\VendorBills\Pages\EditVendorBill;
use App\Filament\Purchasing\Resources\VendorBills\Pages\ListVendorBills;
use App\Filament\Purchasing\Resources\VendorBills\Pages\ViewVendorBill;
use App\Filament\Purchasing\Resources\VendorBills\Schemas\VendorBillForm;
use App\Filament\Purchasing\Resources\VendorBills\Tables\VendorBillsTable;
use App\Models\VendorBill;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class VendorBillResource extends Resource
{
    protected static ?string $model = VendorBill::class;

    protected static UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return VendorBillForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorBillsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorBills::route('/'),
            'create' => CreateVendorBill::route('/create'),
            'edit' => EditVendorBill::route('/{record}/edit'),
            'view' => ViewVendorBill::route('/{record}'),
        ];
    }
}
