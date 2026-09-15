<?php

namespace App\Filament\Accounting\Resources\PaymentTerms;

use App\Filament\Accounting\Resources\PaymentTerms\Pages\CreatePaymentTerm;
use App\Filament\Accounting\Resources\PaymentTerms\Pages\EditPaymentTerm;
use App\Filament\Accounting\Resources\PaymentTerms\Pages\ListPaymentTerms;
use App\Filament\Accounting\Resources\PaymentTerms\Schemas\PaymentTermForm;
use App\Filament\Accounting\Resources\PaymentTerms\Tables\PaymentTermsTable;
use App\Models\PaymentTerm;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentTermResource extends Resource
{
    protected static ?string $model = PaymentTerm::class;

    protected static UnitEnum|string|null $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function form(Schema $schema): Schema
    {
        return PaymentTermForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentTermsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentTerms::route('/'),
            'create' => CreatePaymentTerm::route('/create'),
            'edit' => EditPaymentTerm::route('/{record}/edit'),
        ];
    }
}
