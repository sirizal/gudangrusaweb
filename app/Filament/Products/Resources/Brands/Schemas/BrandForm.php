<?php

namespace App\Filament\Products\Resources\Brands\Schemas;

use App\Enums\BrandStatus;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
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
                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                FileUpload::make('logo_path')
                    ->disk('public')
                    ->image()
                    ->imageEditor()
                    ->directory('brands')
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),
                TextInput::make('website')
                    ->url(),
                Select::make('country_of_origin')
                    ->options(self::countryOptions())
                    ->searchable()
                    ->placeholder('Select country'),
                Select::make('status')
                    ->options(BrandStatus::class)
                    ->required(),
                Toggle::make('is_featured'),
            ]);
    }

    public static function countryOptions(): array
    {
        return [
            'DE' => 'Germany',
            'ID' => 'Indonesia',
            'IN' => 'India',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'MY' => 'Malaysia',
            'TW' => 'Taiwan',
            'TH' => 'Thailand',
            'US' => 'United States',
            'VN' => 'Vietnam',
        ];
    }
}
