<?php

namespace App\Filament\Wms\Resources\Warehouses\Schemas;

use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Warehouse')
                    ->columns(2)
                    ->schema([
                        TextInput::make('warehouse_code')->label('Warehouse code')->disabled()->dehydrated(false)->visibleOn('edit'),
                        TextInput::make('name')->required()->maxLength(255),
                        Select::make('company_id')->label('Company')->relationship('company', 'name')->preload()->searchable()->required(),
                        TextInput::make('phone')->maxLength(30),
                    ]),
                Section::make('Address')
                    ->columns(3)
                    ->schema([
                        Select::make('country_id')->label('Country')
                            ->options(fn (): array => Country::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->preload()->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('province_id', null)),
                        Select::make('province_id')->label('Province')
                            ->options(fn (Get $get): array => Province::query()->when($get('country_id'), fn ($q, $id) => $q->where('country_id', $id))->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->live()
                            ->afterStateUpdated(function (Set $set): mixed {
                                $set('district_id', null);

                                return $set('sub_district_id', null);
                            }),
                        Select::make('district_id')->label('District')
                            ->options(fn (Get $get): array => District::query()->when($get('province_id'), fn ($q, $id) => $q->where('province_id', $id))->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('sub_district_id', null)),
                        Select::make('sub_district_id')->label('Sub district')
                            ->options(fn (Get $get): array => SubDistrict::query()->when($get('district_id'), fn ($q, $id) => $q->where('district_id', $id))->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('village_id', null)),
                        Select::make('village_id')->label('Village')
                            ->options(fn (Get $get): array => Village::query()->when($get('sub_district_id'), fn ($q, $id) => $q->where('sub_district_id', $id))->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()->live()
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('postal_code', Village::find($state)?->postal_code);
                            }),
                        TextInput::make('postal_code')->maxLength(5)->regex('/^\d{5}$/'),
                        TextInput::make('address')->columnSpanFull()->maxLength(255),
                        Toggle::make('is_active')->default(true),
                    ]),
            ]);
    }
}
