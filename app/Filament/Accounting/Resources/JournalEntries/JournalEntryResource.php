<?php

namespace App\Filament\Accounting\Resources\JournalEntries;

use App\Filament\Accounting\Resources\JournalEntries\Pages\CreateJournalEntry;
use App\Filament\Accounting\Resources\JournalEntries\Pages\EditJournalEntry;
use App\Filament\Accounting\Resources\JournalEntries\Pages\ListJournalEntries;
use App\Filament\Accounting\Resources\JournalEntries\Pages\ViewJournalEntry;
use App\Filament\Accounting\Resources\JournalEntries\Schemas\JournalEntryForm;
use App\Filament\Accounting\Resources\JournalEntries\Tables\JournalEntriesTable;
use App\Models\JournalEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class JournalEntryResource extends Resource
{
    protected static ?string $model = JournalEntry::class;

    protected static UnitEnum|string|null $navigationGroup = 'Accounting';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Journal Entries';

    public static function form(Schema $schema): Schema
    {
        return JournalEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JournalEntriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJournalEntries::route('/'),
            'create' => CreateJournalEntry::route('/create'),
            'edit' => EditJournalEntry::route('/{record}/edit'),
            'view' => ViewJournalEntry::route('/{record}'),
        ];
    }
}
