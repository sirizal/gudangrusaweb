<?php

namespace App\Filament\Products\Resources\Products\Schemas;

use App\Enums\ProductStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identification')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state): void {
                                if ((string) $get('slug') !== Str::slug($old ?? '')) {
                                    return;
                                }

                                $set('slug', Str::slug($state ?? ''));
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn (string $state): string => strtoupper($state))
                            ->maxLength(50),
                    ]),
                Section::make('Classification')
                    ->columns(2)
                    ->schema([
                        Select::make('brand_id')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required(),
                                TextInput::make('slug')
                                    ->required()
                                    ->unique(),
                            ]),
                        Select::make('category_id')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('unit_id')
                            ->label('Unit of measure')
                            ->relationship('unit', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),
                Section::make('Pricing & Stock')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('Rp'),
                        TextInput::make('list_price')
                            ->numeric()
                            ->default(null)
                            ->minValue(0)
                            ->prefix('Rp')
                            ->helperText('Original price before discount'),
                        TextInput::make('quantity_on_hand')
                            ->label('Quantity on hand')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        Select::make('status')
                            ->options(ProductStatus::class)
                            ->required(),
                        Toggle::make('is_featured')
                            ->default(false),
                    ]),
                Section::make('Description')
                    ->schema([
                        Textarea::make('description')
                            ->rows(5),
                    ]),
                Section::make('Images')
                    ->schema([
                        Repeater::make('images')
                            ->relationship()
                            ->label('Product images')
                            ->schema([
                                FileUpload::make('image_path')
                                    ->disk('public')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('products')
                                    ->openable()
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->columns(1),
                    ]),
                Section::make('Additional attributes')
                    ->schema([
                        KeyValue::make('metadata')
                            ->keyLabel('Attribute')
                            ->valueLabel('Value')
                            ->addActionLabel('Add attribute'),
                    ]),
            ]);
    }
}
