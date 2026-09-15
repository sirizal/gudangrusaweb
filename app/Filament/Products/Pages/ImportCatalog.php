<?php

namespace App\Filament\Products\Pages;

use App\Services\Products\CatalogImportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class ImportCatalog extends Page
{
    protected static UnitEnum|string|null $navigationGroup = 'Products';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Import Catalog';

    protected static ?string $navigationLabel = 'Import Catalog';

    protected string $view = 'filament.pages.products.import-catalog';

    /**
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $previewRows = null;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    public ?array $validRows = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $previewSummary = null;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download-template')
                ->label('Download template')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): StreamedResponse => static::downloadTemplate($this->data['type'] ?? 'unit')),
        ];
    }

    public static function downloadTemplate(string $type): StreamedResponse
    {
        $headers = match ($type) {
            'brand' => ['name', 'website', 'country_of_origin', 'status', 'is_featured'],
            'category' => ['name', 'code', 'unspsc', 'parent_code', 'description', 'is_active', 'sort_order'],
            'product' => ['name', 'sku', 'description', 'brand', 'category', 'unit', 'price', 'list_price', 'quantity_on_hand', 'status', 'is_featured'],
            default => ['name', 'code', 'symbol'],
        };

        return response()->streamDownload(function () use ($headers): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers, ',', '"', '\\');
            fclose($handle);
        }, $type.'-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function mount(): void
    {
        $this->form->fill(['type' => 'unit']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(2)
            ->schema([
                Select::make('type')
                    ->label('Data type')
                    ->options([
                        'unit' => 'Units',
                        'brand' => 'Brands',
                        'category' => 'Categories',
                        'product' => 'Products',
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
            $preview = app(CatalogImportService::class)->bulkValidate($file->getRealPath(), $state['type']);

            $this->validRows = $preview['rows'];

            $this->previewRows = collect($preview['rows'])
                ->map(fn (array $row): array => [
                    'line' => $row['line'],
                    'status' => 'ok',
                    'name' => $row['name'],
                    'key' => $row['key'],
                    'errors' => [],
                ])
                ->concat(collect($preview['errors'])->map(fn (array $error): array => [
                    'line' => $error['line'],
                    'status' => 'error',
                    'name' => $error['key'],
                    'key' => $error['key'],
                    'errors' => [$error['message']],
                ]))
                ->sortBy('line')
                ->values()
                ->all();

            $this->previewSummary = [
                'valid' => $preview['valid'],
                'invalid' => $preview['invalid'],
            ];
        } catch (InvalidArgumentException $e) {
            $this->previewRows = null;
            $this->validRows = null;
            $this->previewSummary = null;

            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function import(): void
    {
        if ($this->validRows === null || $this->previewSummary === null) {
            return;
        }

        $state = $this->form->getState();
        $type = $state['type'] ?? 'unit';

        try {
            app(CatalogImportService::class)->bulkCommit($this->validRows, $type);

            $this->previewRows = null;
            $this->validRows = null;
            $this->previewSummary = null;

            Notification::make()
                ->title('Catalog imported')
                ->body(ucfirst($type).'s were imported.')
                ->success()
                ->send();
        } catch (InvalidArgumentException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }
}
