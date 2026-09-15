<?php

namespace App\Filament\Accounting\Resources\Companies\RelationManagers;

use App\Enums\CompanyDocumentType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documents';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->schema([
                Select::make('document_type')
                    ->label('Document type')
                    ->options(CompanyDocumentType::class)
                    ->required(),
                TextInput::make('document_number')
                    ->label('Document number')
                    ->maxLength(100),
                TextInput::make('name')
                    ->maxLength(255),
                DatePicker::make('start_date')
                    ->label('Start date')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('End date')
                    ->after('start_date'),
                FileUpload::make('file_path')
                    ->label('Document file')
                    ->disk('public')
                    ->directory('company-documents')
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('document_number')
            ->headerActions([
                CreateAction::make(),
            ])
            ->columns([
                TextColumn::make('document_type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('document_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('file_path')
                    ->label('File')
                    ->icon('heroicon-o-paper-clip')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Download' : '—'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
