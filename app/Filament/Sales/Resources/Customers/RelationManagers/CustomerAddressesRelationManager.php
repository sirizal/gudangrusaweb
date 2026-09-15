<?php

namespace App\Filament\Sales\Resources\Customers\RelationManagers;

use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerAddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $title = 'Addresses';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->schema([
                TextInput::make('address_code')
                    ->label('Address code')
                    ->required()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, $livewire) => $rule->where('customer_id', $livewire->getOwnerRecord()->id))
                    ->helperText('A unique code for this address, e.g. OFFICE or WH1.'),
                TextInput::make('label')
                    ->maxLength(100),
                TextInput::make('phone')
                    ->maxLength(30),
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
                Toggle::make('is_billing')
                    ->label('Billing'),
                Toggle::make('is_shipping')
                    ->label('Shipping'),
                Toggle::make('is_default')
                    ->label('Default'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('address_code')
            ->headerActions([
                CreateAction::make(),
            ])
            ->columns([
                TextColumn::make('address_code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->placeholder('—'),
                TextColumn::make('address')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('village.name')
                    ->label('Village')
                    ->placeholder('—'),
                IconColumn::make('is_billing')
                    ->boolean()
                    ->label('Billing'),
                IconColumn::make('is_shipping')
                    ->boolean()
                    ->label('Shipping'),
                IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
