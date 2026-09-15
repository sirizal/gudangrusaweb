<?php

namespace App\Filament\Accounting\Resources\TaxCodes;

use App\Filament\Accounting\Resources\TaxCodes\Pages\CreateTaxCode;
use App\Filament\Accounting\Resources\TaxCodes\Pages\EditTaxCode;
use App\Filament\Accounting\Resources\TaxCodes\Pages\ListTaxCodes;
use App\Filament\Accounting\Resources\TaxCodes\Schemas\TaxCodeForm;
use App\Filament\Accounting\Resources\TaxCodes\Tables\TaxCodesTable;
use App\Models\TaxCode;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TaxCodeResource extends Resource
{
    protected static ?string $model = TaxCode::class;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 8;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    public static function form(Schema $schema): Schema
    {
        return TaxCodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxCodesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxCodes::route('/'),
            'create' => CreateTaxCode::route('/create'),
            'edit' => EditTaxCode::route('/{record}/edit'),
        ];
    }
}
