<?php

namespace App\Filament\Wms\Resources\StockAdjustments\Schemas;

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

class StockAdjustmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Stock adjustment')
                    ->columns(3)
                    ->schema([
                        Select::make('warehouse_id')->label('Warehouse')
                            ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->preload()->required()->live(),
                        Select::make('location_id')->label('Location')
                            ->options(fn (Get $get): array => WarehouseLocation::query()->when($get('warehouse_id'), fn ($q, $id) => $q->where('warehouse_id', $id))->orderBy('location_code')->pluck('location_code', 'id')->all())
                            ->searchable()->preload()->required(),
                        DatePicker::make('adjustment_date')->required()->default(now()),
                        Textarea::make('reason')->rows(2)->columnSpanFull(),
                    ]),
                Section::make('Lines')
                    ->schema([
                        Repeater::make('lines')
                            ->label('Adjustment lines')
                            ->columns(3)
                            ->schema([
                                Select::make('product_id')->label('Product')
                                    ->options(fn (): array => Product::query()->orderBy('name')->limit(500)->get()->mapWithKeys(fn (Product $p): array => [$p->id => $p->name.' ('.$p->sku.')'])->all())
                                    ->searchable()->preload()->required(),
                                TextInput::make('quantity_delta')->label('Qty (+/-)')->numeric()->required()
                                    ->helperText('Positive to add, negative to remove.'),
                                TextInput::make('unit_cost')->label('Unit cost')->numeric()->default(0)->minValue(0)->prefix('Rp')
                                    ->helperText('Used when adding stock.'),
                            ])
                            ->addActionLabel('Add line')->defaultItems(1)->minItems(1),
                    ]),
            ]);
    }
}
