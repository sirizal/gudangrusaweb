<?php

namespace App\Filament\Geography\Resources\Villages;

use App\Filament\Geography\Resources\Villages\Pages\CreateVillage;
use App\Filament\Geography\Resources\Villages\Pages\EditVillage;
use App\Filament\Geography\Resources\Villages\Pages\ListVillages;
use App\Filament\Geography\Resources\Villages\Schemas\VillageForm;
use App\Filament\Geography\Resources\Villages\Tables\VillagesTable;
use App\Models\Village;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class VillageResource extends Resource
{
    protected static ?string $model = Village::class;

    protected static UnitEnum|string|null $navigationGroup = 'Geography';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    public static function form(Schema $schema): Schema
    {
        return VillageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VillagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVillages::route('/'),
            'create' => CreateVillage::route('/create'),
            'edit' => EditVillage::route('/{record}/edit'),
        ];
    }
}
