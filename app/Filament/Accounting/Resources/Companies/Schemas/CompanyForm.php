<?php

namespace App\Filament\Accounting\Resources\Companies\Schemas;

use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Company')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(10)
                            ->dehydrateStateUsing(fn (string $state): string => strtoupper($state)),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->default(true),
                    ]),
                Section::make('Legal Address')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('country_id')
                            ->label('Country')
                            ->options(fn (): array => Country::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('province_id', null)),
                        Select::make('province_id')
                            ->label('Province')
                            ->options(fn (Get $get): array => Province::query()
                                ->when($get('country_id'), fn ($query, $countryId) => $query->where('country_id', $countryId))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set): mixed {
                                $set('district_id', null);

                                return $set('sub_district_id', null);
                            }),
                        Select::make('district_id')
                            ->label('District')
                            ->options(fn (Get $get): array => District::query()
                                ->when($get('province_id'), fn ($query, $provinceId) => $query->where('province_id', $provinceId))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('sub_district_id', null)),
                        Select::make('sub_district_id')
                            ->label('Sub district')
                            ->options(fn (Get $get): array => SubDistrict::query()
                                ->when($get('district_id'), fn ($query, $districtId) => $query->where('district_id', $districtId))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('village_id', null)),
                        Select::make('village_id')
                            ->label('Village')
                            ->options(fn (Get $get): array => Village::query()
                                ->when($get('sub_district_id'), fn ($query, $subDistrictId) => $query->where('sub_district_id', $subDistrictId))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                                $set('postal_code', Village::find($state)?->postal_code);
                            }),
                        TextInput::make('postal_code')
                            ->maxLength(5)
                            ->regex('/^\d{5}$/'),
                        TextInput::make('address')
                            ->columnSpanFull()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->maxLength(30),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(100),
                        TextInput::make('website')
                            ->url()
                            ->maxLength(100),
                    ]),
                Section::make('Tax Information')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('npwp')
                            ->label('NPWP')
                            ->regex('/^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/')
                            ->placeholder('00.000.000.0-000.000')
                            ->helperText('Indonesian tax ID, e.g. 01.234.567.8-901.234.')
                            ->maxLength(20),
                        TextInput::make('nib')
                            ->label('NIB')
                            ->maxLength(50),
                        Toggle::make('is_pkp')
                            ->label('PKP (VAT registered)'),
                        TextInput::make('tax_office')
                            ->label('Tax office (KPP)')
                            ->maxLength(100),
                        DatePicker::make('tax_registration_date')
                            ->label('Tax registration date'),
                    ]),
            ]);
    }
}
