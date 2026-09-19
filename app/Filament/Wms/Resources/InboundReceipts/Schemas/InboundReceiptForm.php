<?php

namespace App\Filament\Wms\Resources\InboundReceipts\Schemas;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
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

class InboundReceiptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Inbound receipt')
                    ->columns(3)
                    ->schema([
                        Select::make('purchase_order_id')->label('Purchase order')
                            ->options(fn (): array => PurchaseOrder::query()
                                ->whereIn('status', ['open', 'partially_received'])
                                ->orderByDesc('po_date')->pluck('po_code', 'id')->all())
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
                        DatePicker::make('receipt_date')->required()->default(now()),
                        Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),
                Section::make('Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Receive inventory lines')
                            ->columns(4)
                            ->schema([
                                Select::make('purchase_order_line_id')->label('PO line')
                                    ->options(function (Get $get): array {
                                        $poId = $get('../../purchase_order_id');
                                        if (! $poId) {
                                            return [];
                                        }

                                        return PurchaseOrderLine::query()
                                            ->where('purchase_order_id', $poId)
                                            ->where('purchase_type', 'inventory')
                                            ->whereColumn('received_quantity', '<', 'quantity')
                                            ->get()
                                            ->mapWithKeys(fn (PurchaseOrderLine $l): array => [$l->id => ($l->product?->name ?? 'Line #'.$l->id).' — remaining '.($l->quantity - $l->received_quantity)])
                                            ->all();
                                    })
                                    ->searchable()->preload()->required(),
                                TextInput::make('quantity_received')->numeric()->required()->minValue(1),
                                TextInput::make('unit_cost')->label('Unit cost')->disabled()->dehydrated(false)
                                    ->formatStateUsing(function (Get $get): string {
                                        $l = PurchaseOrderLine::find($get('purchase_order_line_id'));

                                        return $l ? number_format((float) $l->unit_price, 0, ',', '.') : '—';
                                    }),
                                TextInput::make('po_qty')->label('Ordered qty')->disabled()->dehydrated(false)
                                    ->formatStateUsing(function (Get $get): string {
                                        $l = PurchaseOrderLine::find($get('purchase_order_line_id'));

                                        return $l ? (string) $l->quantity : '—';
                                    }),
                            ])
                            ->addActionLabel('Add line')->defaultItems(1)->minItems(1),
                    ]),
            ]);
    }
}
