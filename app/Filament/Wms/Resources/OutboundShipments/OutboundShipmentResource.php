<?php

namespace App\Filament\Wms\Resources\OutboundShipments;

use App\Filament\Wms\Resources\OutboundShipments\Pages\CreateOutboundShipment;
use App\Filament\Wms\Resources\OutboundShipments\Pages\ListOutboundShipments;
use App\Filament\Wms\Resources\OutboundShipments\Pages\ViewOutboundShipment;
use App\Filament\Wms\Resources\OutboundShipments\Schemas\OutboundShipmentForm;
use App\Filament\Wms\Resources\OutboundShipments\Tables\OutboundShipmentsTable;
use App\Models\OutboundShipment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OutboundShipmentResource extends Resource
{
    protected static ?string $model = OutboundShipment::class;

    protected static UnitEnum|string|null $navigationGroup = 'Warehouse';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowRightOnRectangle;

    public static function form(Schema $schema): Schema
    {
        return OutboundShipmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutboundShipmentsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOutboundShipments::route('/'),
            'create' => CreateOutboundShipment::route('/create'),
            'view' => ViewOutboundShipment::route('/{record}'),
        ];
    }
}
