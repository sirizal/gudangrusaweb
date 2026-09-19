<?php

namespace App\Filament\Purchasing\Resources\PurchaseRequests;

use App\Filament\Purchasing\Resources\PurchaseRequests\Pages\CreatePurchaseRequest;
use App\Filament\Purchasing\Resources\PurchaseRequests\Pages\EditPurchaseRequest;
use App\Filament\Purchasing\Resources\PurchaseRequests\Pages\ListPurchaseRequests;
use App\Filament\Purchasing\Resources\PurchaseRequests\Pages\ViewPurchaseRequest;
use App\Filament\Purchasing\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Filament\Purchasing\Resources\PurchaseRequests\Tables\PurchaseRequestsTable;
use App\Models\PurchaseRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static UnitEnum|string|null $navigationGroup = 'Purchasing';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentPlus;

    public static function form(Schema $schema): Schema
    {
        return PurchaseRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseRequestsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPurchaseRequests::route('/'),
            'create' => CreatePurchaseRequest::route('/create'),
            'edit' => EditPurchaseRequest::route('/{record}/edit'),
            'view' => ViewPurchaseRequest::route('/{record}'),
        ];
    }
}
