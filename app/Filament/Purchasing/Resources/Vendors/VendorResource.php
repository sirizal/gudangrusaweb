<?php

namespace App\Filament\Purchasing\Resources\Vendors;

use App\Filament\Purchasing\Resources\Vendors\Pages\CreateVendor;
use App\Filament\Purchasing\Resources\Vendors\Pages\EditVendor;
use App\Filament\Purchasing\Resources\Vendors\Pages\ListVendors;
use App\Filament\Purchasing\Resources\Vendors\RelationManagers\VendorAddressesRelationManager;
use App\Filament\Purchasing\Resources\Vendors\Schemas\VendorForm;
use App\Filament\Purchasing\Resources\Vendors\Tables\VendorsTable;
use App\Models\Vendor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class VendorResource extends Resource
{
    protected static ?string $model = Vendor::class;

    protected static UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function form(Schema $schema): Schema
    {
        return VendorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VendorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [VendorAddressesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}
