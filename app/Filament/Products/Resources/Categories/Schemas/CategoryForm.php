<?php

namespace App\Filament\Products\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
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
                TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (string $state): string => strtoupper($state))
                    ->maxLength(50),
                TextInput::make('unspsc')
                    ->numeric()
                    ->maxLength(12)
                    ->helperText('United Nations Standard Products and Services Code'),
                FileUpload::make('logo_path')
                    ->disk('public')
                    ->image()
                    ->directory('categories')
                    ->openable()
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('parent_id')
                    ->label('Parent category')
                    ->relationship(
                        'parent',
                        'name',
                        ignoreRecord: true,
                    )
                    ->preload()
                    ->searchable()
                    ->helperText('Optional. Select a parent to nest this category under another.'),
                Toggle::make('is_active')
                    ->default(true),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }
}
