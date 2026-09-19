<?php

namespace App\Filament\Wms\Resources\Warehouses\RelationManagers;

use App\Enums\WarehouseLocationType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehouseLocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    protected static ?string $title = 'Locations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                TextInput::make('location_code')->label('Location code')->required()->maxLength(30)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule, $livewire) => $rule->where('warehouse_id', $livewire->getOwnerRecord()->id)),
                TextInput::make('name')->maxLength(255),
                Select::make('type')->options(WarehouseLocationType::class)->required(),
                TextInput::make('weight_capacity')->numeric()->default(0)->minValue(0)->suffix('kg'),
                TextInput::make('volume_capacity')->numeric()->default(0)->minValue(0)->suffix('m3'),
                Toggle::make('is_active')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('location_code')
            ->headerActions([CreateAction::make()])
            ->columns([
                TextColumn::make('location_code')->searchable()->sortable(),
                TextColumn::make('name')->placeholder('—'),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('weight_capacity')->numeric()->label('Weight cap.'),
                TextColumn::make('volume_capacity')->numeric()->label('Volume cap.'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
