<?php

namespace App\Filament\Products\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Auto-generated on create')
                            ->helperText('Format S followed by seven digits, e.g. S0000001')
                            ->maxLength(50),
                        TextInput::make('customer_sku')
                            ->label('Customer SKU')
                            ->maxLength(100),
                        TextInput::make('model_number')
                            ->maxLength(255),
                        TextInput::make('size')
                            ->maxLength(50),
                        TextInput::make('color')
                            ->maxLength(50),
                        FileUpload::make('image_path')
                            ->disk('public')
                            ->image()
                            ->imageEditor()
                            ->directory('products/variants')
                            ->openable()
                            ->columnSpanFull(),
                        TextInput::make('unit')
                            ->label('Unit of measure')
                            ->placeholder('e.g. pcs, box, set')
                            ->maxLength(50),
                        TextInput::make('selling_price')
                            ->label('Selling price')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->prefix('Rp'),
                        TextInput::make('available_stock')
                            ->label('Available stock')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        TextInput::make('leadtime')
                            ->label('Lead time (days)')
                            ->numeric()
                            ->default(null)
                            ->minValue(0)
                            ->maxValue(365)
                            ->suffix('days'),
                        TextInput::make('supply_city')
                            ->maxLength(255),
                        KeyValue::make('metadata')
                            ->label('Metadata')
                            ->addActionLabel('Add attribute')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('model_number')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl(fn (): string => ''),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_sku')
                    ->label('Customer SKU')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('model_number')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('size')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('color')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('unit')
                    ->label('Unit')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('selling_price')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('available_stock')
                    ->label('Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('leadtime')
                    ->label('Lead time')
                    ->formatStateUsing(fn (?int $state): string => $state === null ? '—' : $state.' days')
                    ->toggleable(),
                TextColumn::make('supply_city')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('metadata')
                    ->label('Attributes')
                    ->formatStateUsing(function (mixed $state): string {
                        if (is_string($state)) {
                            $state = json_decode($state, true);
                        }

                        if (! is_array($state) || $state === []) {
                            return '—';
                        }

                        return implode(', ', array_map(
                            fn (string $key, mixed $value): string => "{$key}: {$value}",
                            array_keys($state),
                            $state,
                        ));
                    })
                    ->limit(40)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalWidth(Width::ExtraLarge),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth(Width::ExtraLarge),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
