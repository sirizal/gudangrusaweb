<?php

namespace App\Filament\Wms\Resources\StockTransfers\Schemas;

use App\Models\Product;
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

class StockTransferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Stock transfer')
                    ->columns(3)
                    ->schema([
                        Select::make('warehouse_id')->label('Warehouse')
                            ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->preload()->required()->live(),
                        Select::make('from_location_id')->label('From location')
                            ->options(fn (Get $get): array => WarehouseLocation::query()->when($get('warehouse_id'), fn ($q, $id) => $q->where('warehouse_id', $id))->orderBy('location_code')->pluck('location_code', 'id')->all())
                            ->searchable()->preload()->required(),
                        Select::make('to_location_id')->label('To location')
                            ->options(fn (Get $get): array => WarehouseLocation::query()->when($get('warehouse_id'), fn ($q, $id) => $q->where('warehouse_id', $id))->orderBy('location_code')->pluck('location_code', 'id')->all())
                            ->searchable()->preload()->required(),
                        DatePicker::make('transfer_date')->required()->default(now()),
                        Textarea::make('notes')->rows(2)->columnSpanFull(),
                    ]),
                Section::make('Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Transfer lines')
                            ->columns(2)
                            ->schema([
                                Select::make('product_id')->label('Product')
                                    ->options(fn (): array => Product::query()->orderBy('name')->limit(500)->get()->mapWithKeys(fn (Product $p): array => [$p->id => $p->name.' ('.$p->sku.')'])->all())
                                    ->searchable()->preload()->required(),
                                TextInput::make('quantity')->numeric()->required()->minValue(1),
                            ])
                            ->addActionLabel('Add line')->defaultItems(1)->minItems(1),
                    ]),
            ]);
    }
}
