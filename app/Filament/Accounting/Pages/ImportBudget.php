<?php

namespace App\Filament\Accounting\Pages;

use App\Enums\Role;
use App\Models\Account;
use App\Models\Budget;
use App\Services\Accounting\BudgetImportService;
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

class ImportBudget extends Page
{
    protected static UnitEnum|string|null $navigationGroup = 'Budgeting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Import Budget';

    protected static ?string $navigationLabel = 'Import Budget';

    protected string $view = 'filament.pages.accounting.import-budget';

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download-template')
                ->label('Download template')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn (): StreamedResponse => static::downloadTemplate()),
        ];
    }

    public static function downloadTemplate(): StreamedResponse
    {
        $accounts = Account::query()
            ->where('is_postable', true)
            ->where('is_group', false)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get(['account_code', 'account_name']);

        $monthLabels = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        return response()->streamDownload(function () use ($accounts, $monthLabels): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Account Code',
                'Account Name',
                'Cost Center',
                'Department',
                'Project',
                ...$monthLabels,
            ], ',', '"', '\\');

            foreach ($accounts as $account) {
                fputcsv($handle, [
                    $account->account_code,
                    $account->account_name,
                    '',
                    '',
                    '',
                    ...array_fill(0, 12, '0'),
                ], ',', '"', '\\');
            }

            fclose($handle);
        }, 'budget-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function mount(): void
    {
        $this->form->fill([
            'mode' => 'upsert',
            'budgetId' => Budget::query()->orderBy('budget_code')->first()?->id,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->columns(3)
            ->schema([
                Select::make('budgetId')
                    ->label('Budget')
                    ->options(fn (): array => Budget::query()
                        ->orderBy('budget_code')
                        ->get()
                        ->mapWithKeys(fn (Budget $budget): array => [$budget->id => $budget->budget_code.' — '.$budget->budget_name])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('mode')
                    ->label('Import mode')
                    ->options([
                        'upsert' => 'Update or add lines',
                        'replace' => 'Replace all lines',
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
            $preview = app(BudgetImportService::class)->parse($file->getRealPath());
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

        $state = $this->form->getState();
        $budget = Budget::findOrFail($state['budgetId']);
        $mode = $state['mode'] ?? 'upsert';

        try {
            app(BudgetImportService::class)->commit(
                $budget,
                ['rows' => $this->previewRows],
                $mode,
                auth()->user(),
            );

            $this->previewRows = null;
            $this->previewSummary = null;

            Notification::make()
                ->title('Budget imported')
                ->body('Valid rows were imported into '.$budget->budget_code.'.')
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
        return auth()->user()?->hasRole(Role::BudgetOwner, Role::Accountant, Role::FinanceManager, Role::SuperAdmin) ?? false;
    }
}
