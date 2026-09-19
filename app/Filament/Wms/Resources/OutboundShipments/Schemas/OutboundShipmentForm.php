<?php

namespace App\Filament\Wms\Resources\OutboundShipments\Schemas;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class OutboundShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Outbound shipment')
                    ->columns(3)
                    ->schema([
                        Select::make('sales_order_id')->label('Sales order')
                            ->options(fn (): array => SalesOrder::query()
                                ->where('status', 'delivered')
                                ->orderByDesc('order_date')->pluck('order_code', 'id')->all())
                            ->searchable()->preload()->required()->live(),
                        Select::make('warehouse_id')->label('Warehouse')
                            ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->preload()->required()->live(),
                        Select::make('location_id')->label('Location')
                            ->options(fn (Get $get): array => WarehouseLocation::query()
                                ->when($get('warehouse_id'), fn ($q, $id) => $q->where('warehouse_id', $id))
                                ->orderBy('location_code')
                                ->get()->mapWithKeys(fn ($l): array => [$l->id => $l->location_code.' ('.$l->type->getLabel().')'])->all())
                            ->searchable()->preload()->required(),
                        DatePicker::make('shipment_date')->required()->default(now()),
                        Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),
                Section::make('Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Ship lines')
                            ->columns(3)
                            ->schema([
                                Select::make('sales_order_line_id')->label('SO line')
                                    ->options(function (Get $get): array {
                                        $soId = $get('../../sales_order_id');
                                        if (! $soId) {
                                            return [];
                                        }

                                        return SalesOrderLine::query()
                                            ->where('sales_order_id', $soId)
                                            ->whereNotNull('product_id')
                                            ->get()
                                            ->mapWithKeys(fn (SalesOrderLine $l): array => [$l->id => $l->product?->name.' x'.$l->quantity])
                                            ->all();
                                    })
                                    ->searchable()->preload()->required(),
                                TextInput::make('quantity_shipped')->numeric()->required()->minValue(1),
                                TextInput::make('ordered_qty')->label('Ordered qty')->disabled()->dehydrated(false)
                                    ->formatStateUsing(function (Get $get): string {
                                        $l = SalesOrderLine::find($get('sales_order_line_id'));

                                        return $l ? (string) $l->quantity : '—';
                                    }),
                            ])
                            ->addActionLabel('Add line')->defaultItems(1)->minItems(1),
                    ]),
            ]);
    }
}
