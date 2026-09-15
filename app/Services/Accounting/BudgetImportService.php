<?php

namespace App\Services\Accounting;

use App\Enums\BudgetStatus;
use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Reader\Common\Creator\ReaderFactory;

class BudgetImportService
{
    /**
     * Header labels that map to the import columns. Month columns are kept
     * as the canonical names to make the parser resilient to header drift.
     *
     * @var array<string, string>
     */
    protected array $headerMap = [
        'account code' => 'account_code',
        'account name' => 'account_name',
        'cost center' => 'cost_center',
        'cost center code' => 'cost_center',
        'department' => 'department',
        'department code' => 'department',
        'project' => 'project',
        'project code' => 'project',
        'description' => 'description',
    ];

    /**
     * Parse and validate a CSV/XLSX budget file without mutating anything.
     *
     * Returns structured preview rows grouped by status; the caller must show
     * this preview before calling commit().
     *
     * @return array{
     *     columns: array<int, string>,
     *     rows: array<int, array<string, mixed>>,
     *     valid: int,
     *     invalid: int,
     * }
     */
    public function parse(string $path): array
    {
        $rows = $this->readRows($path);

        if ($rows === []) {
            throw new InvalidArgumentException('The file does not contain any data rows.');
        }

        $headers = $this->mapHeaders(array_shift($rows));
        $preview = [];

        foreach ($rows as $index => $raw) {
            $preview[] = $this->validateRow($raw, $headers, $index + 2, $preview);
        }

        return [
            'columns' => $headers,
            'rows' => $preview,
            'valid' => collect($preview)->where('status', 'ok')->count(),
            'invalid' => collect($preview)->where('status', 'error')->count(),
        ];
    }

    /**
     * Commit the valid, already-parsed rows into a budget.
     *
     * @param  array<string, mixed>  $parsed
     * @param  string  $mode  'replace' wipes existing lines first, 'upsert' updates or inserts by the unique line key.
     */
    public function commit(Budget $budget, array $parsed, string $mode = 'upsert', ?User $actor = null): Budget
    {
        if (in_array($budget->status, [BudgetStatus::Approved, BudgetStatus::Locked], true)) {
            throw new InvalidArgumentException('Approved or locked budgets cannot be edited by import.');
        }

        $validRows = collect($parsed['rows'] ?? [])->where('status', 'ok')->values();

        if ($validRows->isEmpty()) {
            throw new InvalidArgumentException('There are no valid rows to import.');
        }

        DB::transaction(function () use ($budget, $validRows, $mode): void {
            if ($mode === 'replace') {
                $budget->lines()->delete();
            }

            foreach ($validRows as $row) {
                $this->upsertLine($budget, $row);
            }

            $budget->recordAudit('budget_import', [
                'budget_code' => $budget->budget_code,
                'rows' => $validRows->count(),
                'mode' => $mode,
            ]);
        });

        return $budget->load('lines.months');
    }

    /**
     * Read all rows from a CSV or XLSX file as arrays of cell values.
     *
     * @return array<int, array<int, string|float|int|null>>
     */
    protected function readRows(string $path): array
    {
        try {
            $reader = ReaderFactory::createFromFile($path);
        } catch (UnsupportedTypeException) {
            $reader = ReaderFactory::createFromFileByMimeType($path);
        }

        $reader->open($path);

        $rows = [];

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->toArray();

                if (collect($cells)->every(fn ($cell): bool => $cell === null || $cell === '')) {
                    continue;
                }

                $rows[] = array_map(fn ($cell): string|float|int|null => $cell ?? null, $cells);
            }

            break;
        }

        $reader->close();

        return $rows;
    }

    /**
     * Map the header row to the internal import column names.
     *
     * @param  array<int, string|float|int|null>  $headerRow
     * @return array<int, string>
     */
    protected function mapHeaders(array $headerRow): array
    {
        $columns = [];

        foreach ($headerRow as $cell) {
            $label = strtolower(trim((string) $cell));

            if (isset($this->headerMap[$label])) {
                $columns[] = $this->headerMap[$label];

                continue;
            }

            $month = $this->monthNumber($label);

            $columns[] = $month !== null ? 'month_'.$month : 'unknown_'.count($columns);
        }

        return $columns;
    }

    /**
     * Normalize a header label to a 1-12 month number, or null.
     */
    protected function monthNumber(string $label): ?int
    {
        $trimmed = trim($label);

        if (preg_match('/^\d{1,2}$/', $trimmed) && (int) $trimmed >= 1 && (int) $trimmed <= 12) {
            return (int) $trimmed;
        }

        $names = [
            'january', 'jan', 'february', 'feb', 'march', 'mar', 'april', 'apr',
            'may', 'june', 'jun', 'july', 'jul', 'august', 'aug', 'september', 'sep', 'sept',
            'october', 'oct', 'november', 'nov', 'december', 'dec',
        ];

        $index = array_search($trimmed, $names, true);

        if ($index === false) {
            return null;
        }

        return (int) floor($index / 2) + 1;
    }

    /**
     * Validate a single data row, matching accounts by code (falling back to
     * name) and cost center / department / project identifiers by code or name.
     *
     * @param  array<int, string|float|int|null>  $raw
     * @param  array<int, string>  $columns
     * @param  int  $lineNumber  Human-readable row number for error messages.
     * @param  array<int, array<string, mixed>>  $previous  Already-validated preview rows for duplicate detection.
     * @return array<string, mixed>
     */
    protected function validateRow(array $raw, array $columns, int $lineNumber, array $previous): array
    {
        $cell = function (string $key) use ($columns, $raw): string|float|int|null {
            $index = array_search($key, $columns, true);

            if ($index === false) {
                return null;
            }

            return $raw[$index] ?? null;
        };
        $text = fn (string|float|int|null $value): string => trim((string) $value);

        $accountCode = $text($cell('account_code'));
        $monthly = [];

        foreach (range(1, 12) as $month) {
            $key = array_search('month_'.$month, $columns, true);

            if ($key === false) {
                $monthly[$month] = 0.0;

                continue;
            }

            $value = trim((string) ($raw[$key] ?? 0));

            if ($value === '') {
                $monthly[$month] = 0.0;

                continue;
            }

            if (! is_numeric($value)) {
                return $this->errorRow($lineNumber, $accountCode ?? '—', 'Month '.$month.' amount must be numeric.');
            }

            $amount = (float) $value;

            if ($amount < 0) {
                return $this->errorRow($lineNumber, $accountCode ?? '—', 'Budget amounts must not be negative.');
            }

            $monthly[$month] = $amount;
        }

        if ($accountCode === '') {
            return $this->errorRow($lineNumber, '—', 'Account code is required.');
        }

        $account = Account::query()
            ->where('account_code', $accountCode)
            ->orWhere('account_name', $accountCode)
            ->first();

        if (! $account || ! $account->is_active) {
            return $this->errorRow($lineNumber, $accountCode, 'Account does not exist or is inactive.');
        }

        if ($account->is_group || ! $account->is_postable) {
            return $this->errorRow($lineNumber, $accountCode, 'Budget lines require a postable, non-group account.');
        }

        $costCenter = $this->resolveClass($text($cell('cost_center')), CostCenter::class);
        $department = $this->resolveClass($text($cell('department')), Department::class);
        $project = $this->resolveClass($text($cell('project')), Project::class);

        $resolutions = [
            ['Cost center', $text($cell('cost_center')), $costCenter],
            ['Department', $text($cell('department')), $department],
            ['Project', $text($cell('project')), $project],
        ];

        $errors = [];

        foreach ($resolutions as [$label, $value, $model]) {
            if ($value !== '' && ! $model) {
                $errors[] = $label.' "'.$value.'" could not be found.';
            }
        }

        if ($errors !== []) {
            return $this->errorRow($lineNumber, $accountCode, implode(' ', $errors));
        }

        $key = implode('|', [
            $account->id,
            $costCenter?->id ?? '',
            $department?->id ?? '',
            $project?->id ?? '',
        ]);

        foreach ($previous as $row) {
            if (($row['key'] ?? null) === $key) {
                return $this->errorRow($lineNumber, $accountCode, 'Duplicate budget line for the same account and dimensions.');
            }
        }

        $annual = round(array_sum($monthly), 2);

        return [
            'line' => $lineNumber,
            'key' => $key,
            'status' => 'ok',
            'errors' => [],
            'account_code' => $account->account_code,
            'account_name' => $account->account_name,
            'cost_center' => $costCenter?->name,
            'department' => $department?->name,
            'project' => $project?->name,
            'account_id' => $account->id,
            'cost_center_id' => $costCenter?->id,
            'department_id' => $department?->id,
            'project_id' => $project?->id,
            'description' => $text($cell('description')) ?: null,
            'months' => $monthly,
            'annual' => $annual,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function errorRow(int $line, string $accountCode, string $message): array
    {
        return [
            'line' => $line,
            'key' => null,
            'status' => 'error',
            'errors' => [$message],
            'account_code' => $accountCode,
            'account_name' => null,
            'cost_center' => null,
            'department' => null,
            'project' => null,
            'account_id' => null,
            'cost_center_id' => null,
            'department_id' => null,
            'project_id' => null,
            'description' => null,
            'months' => [],
            'annual' => 0,
        ];
    }

    /**
     * Resolve a model by code or name; null when the value is empty.
     */
    protected function resolveClass(string $value, string $class): mixed
    {
        if ($value === '') {
            return null;
        }

        return $class::where('code', $value)->orWhere('name', $value)->first();
    }

    /**
     * Update an existing budget line by its unique key, or add a new one.
     *
     * @param  array<string, mixed>  $row
     */
    protected function upsertLine(Budget $budget, array $row): void
    {
        $line = BudgetLine::query()
            ->where('budget_id', $budget->id)
            ->where('account_id', $row['account_id'])
            ->where('cost_center_id', $row['cost_center_id'])
            ->where('department_id', $row['department_id'])
            ->where('project_id', $row['project_id'])
            ->first();

        if ($line) {
            $line->update(['description' => $row['description'] ?? $line->description, 'annual_amount' => $row['annual']]);
            $line->months()->delete();

            foreach ($row['months'] as $month => $amount) {
                $line->months()->create(['month' => $month, 'amount' => $amount]);
            }

            return;
        }

        $line = $budget->lines()->create([
            'account_id' => $row['account_id'],
            'cost_center_id' => $row['cost_center_id'],
            'department_id' => $row['department_id'],
            'project_id' => $row['project_id'],
            'description' => $row['description'] ?? null,
            'annual_amount' => $row['annual'],
        ]);

        foreach ($row['months'] as $month => $amount) {
            $line->months()->create(['month' => $month, 'amount' => $amount]);
        }
    }
}
