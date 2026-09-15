<?php

namespace App\Filament\Geography\Pages;

use App\Enums\Role;
use App\Services\Geography\GeographyImportService;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use UnitEnum;

class ImportGeography extends Page
{
    protected static UnitEnum|string|null $navigationGroup = 'Geography';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Import Geography';

    protected string $view = 'filament.pages.geography.import-geography';

    /**
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $previewRows = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $previewSummary = null;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(['level' => 'province']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(2)
            ->schema([
                Select::make('level')
                    ->label('Administrative level')
                    ->options([
                        'province' => 'Province',
                        'district' => 'District',
                        'sub_district' => 'Sub District',
                        'village' => 'Village',
                    ])
                    ->required(),
                FileUpload::make('file')
                    ->label('CSV / Excel file')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'text/comma-separated-values',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->required()
                    ->storeFiles(false),
            ]);
    }

    public function preview(): void
    {
        $this->form->validate();

        $state = $this->form->getState();

        $file = $state['file'] ?? null;

        if (is_array($file)) {
            $file = $file[0] ?? null;
        }

        if (! $file instanceof TemporaryUploadedFile) {
            Notification::make()->title('Please choose a CSV or Excel file to import.')->warning()->send();

            return;
        }

        try {
            $preview = app(GeographyImportService::class)->parse($file->getRealPath(), $state['level']);
            $this->previewRows = $preview['rows'];
            $this->previewSummary = [
                'valid' => $preview['valid'],
                'invalid' => $preview['invalid'],
            ];
        } catch (InvalidArgumentException $e) {
            $this->previewRows = null;
            $this->previewSummary = null;

            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function import(): void
    {
        if ($this->previewRows === null || $this->previewSummary === null) {
            return;
        }

        $level = $this->form->getState()['level'] ?? 'province';

        try {
            app(GeographyImportService::class)->commit(
                ['rows' => $this->previewRows],
                $level,
            );

            $this->previewRows = null;
            $this->previewSummary = null;

            Notification::make()
                ->title('Geography imported')
                ->body(ucwords(str_replace('_', ' ', $level)).' rows were imported.')
                ->success()
                ->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->hasRole(Role::SuperAdmin) ?? false;
    }
}
