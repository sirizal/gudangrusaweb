<?php

namespace App\Services\Geography;

use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Reader\Common\Creator\ReaderFactory;

class GeographyImportService
{
    /**
     * Header labels that map to the internal import column names.
     *
     * @var array<string, string>
     */
    protected array $headerMap = [
        'code' => 'code',
        'kode' => 'code',
        'name' => 'name',
        'nama' => 'name',
        'parent code' => 'parent_code',
        'parent_code' => 'parent_code',
        'parent' => 'parent_code',
        'province code' => 'parent_code',
        'province_code' => 'parent_code',
        'kabupaten code' => 'parent_code',
        'kabupaten_code' => 'parent_code',
        'district code' => 'parent_code',
        'regency_code' => 'parent_code',
        'kecamatan code' => 'parent_code',
        'kecamatan_code' => 'parent_code',
        'sub district code' => 'parent_code',
        'sub_district_code' => 'parent_code',
        'country code' => 'country_code',
        'country_code' => 'country_code',
        'negara' => 'country_code',
        'postal code' => 'postal_code',
        'postal_code' => 'postal_code',
        'kode pos' => 'postal_code',
        'kodepos' => 'postal_code',
    ];

    /**
     * Parse and validate a CSV/XLSX file for one administrative level without
     * mutating anything. The caller must show the preview before committing.
     *
     * @param  string  $level  province|district|sub_district|village
     * @return array{
     *     columns: array<int, string>,
     *     rows: array<int, array<string, mixed>>,
     *     valid: int,
     *     invalid: int,
     * }
     */
    public function parse(string $path, string $level): array
    {
        $this->assertLevel($level);

        $rows = $this->readRows($path);

        if ($rows === []) {
            throw new InvalidArgumentException('The file does not contain any data rows.');
        }

        $headers = $this->mapHeaders(array_shift($rows));
        $preview = [];

        foreach ($rows as $index => $raw) {
            $preview[] = $this->validateRow($raw, $headers, $index + 2, $level, $preview);
        }

        return [
            'columns' => $headers,
            'rows' => $preview,
            'valid' => collect($preview)->where('status', 'ok')->count(),
            'invalid' => collect($preview)->where('status', 'error')->count(),
        ];
    }

    /**
     * Commit the valid, already-parsed rows, upserting by (parent, code).
     *
     * @param  array<string, mixed>  $parsed
     * @param  string  $level  province|district|sub_district|village
     */
    public function commit(array $parsed, string $level): void
    {
        $this->assertLevel($level);

        $validRows = collect($parsed['rows'] ?? [])->where('status', 'ok')->values();

        if ($validRows->isEmpty()) {
            throw new InvalidArgumentException('There are no valid rows to import.');
        }

        $config = $this->levelConfig($level);

        DB::transaction(function () use ($config, $validRows): void {
            foreach ($validRows as $row) {
                $config['model']::query()
                    ->where($config['parent_column'], $row['parent_id'])
                    ->where('code', $row['code'])
                    ->firstOrCreate(
                        [
                            $config['parent_column'] => $row['parent_id'],
                            'code' => $row['code'],
                        ],
                        array_filter([
                            'name' => $row['name'],
                            'postal_code' => $row['postal_code'] ?? null,
                            'is_active' => true,
                        ]),
                    );
            }
        });
    }

    /**
     * Fast bulk validation of a CSV/XLSX file for one administrative level.
     *
     * Unlike parse(), this resolves parents from a preloaded code map instead of
     * querying the database per row, so it can handle the full Indonesia dataset
     * (~83k villages) in one pass. It does not mutate anything.
     *
     * @param  string  $level  province|district|sub_district|village
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     valid: int,
     *     invalid: int,
     *     errors: array<int, array{line: int, code: string, message: string}>,
     * }
     */
    public function bulkValidate(string $path, string $level): array
    {
        $this->assertLevel($level);
        $config = $this->levelConfig($level);

        $rows = $this->readRows($path);

        if ($rows === []) {
            throw new InvalidArgumentException('The file does not contain any data rows.');
        }

        $columns = $this->mapHeaders(array_shift($rows));

        /** @var array<string, int> $parentMap code => id */
        $parentMap = $config['parent']::query()->pluck('id', 'code')->all();

        $defaultCountryId = null;

        if ($level === 'province') {
            $defaultCountryId = Country::query()->where('code', 'IDN')->value('id')
                ?? Country::query()->value('id');
        }

        $validRows = [];
        $errors = [];
        $seen = [];

        foreach ($rows as $index => $raw) {
            $line = $index + 2;

            $cell = function (string $key) use ($columns, $raw): string|float|int|null {
                $keyIndex = array_search($key, $columns, true);

                if ($keyIndex === false) {
                    return null;
                }

                return $raw[$keyIndex] ?? null;
            };
            $text = fn (string|float|int|null $value): string => trim((string) $value);

            $code = $text($cell('code'));
            $name = $text($cell('name'));
            $postal = $text($cell('postal_code'));
            $error = null;
            $parentId = null;

            if ($code === '') {
                $error = 'Code is required.';
            } elseif ($name === '') {
                $error = 'Name is required.';
            } else {
                $parentId = $this->resolveParentId(
                    $text($cell('parent_code')),
                    $text($cell('country_code')),
                    $level,
                    $parentMap,
                    $defaultCountryId,
                    $error,
                );
            }

            if ($error === null && $level === 'village') {
                if ($postal === '') {
                    $error = 'Postal code is required for villages.';
                } elseif (! preg_match('/^\d{5}$/', $postal)) {
                    $error = 'Postal code must be exactly 5 digits.';
                }
            }

            if ($error === null) {
                $key = $parentId.'|'.$code;

                if (isset($seen[$key])) {
                    $error = 'Duplicate row for the same parent and code.';
                } else {
                    $seen[$key] = true;
                }
            }

            if ($error !== null) {
                $errors[] = ['line' => $line, 'code' => $code, 'message' => $error];

                continue;
            }

            $validRows[] = [
                'line' => $line,
                $config['parent_column'] => $parentId,
                'code' => $code,
                'name' => $name,
                'postal_code' => $level === 'village' ? $postal : null,
            ];
        }

        return [
            'rows' => $validRows,
            'valid' => count($validRows),
            'invalid' => count($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Bulk-insert pre-validated rows, skipping any that already exist.
     *
     * insertOrIgnore emits ON CONFLICT DO NOTHING (no target), which respects the
     * partial unique indexes (WHERE deleted_at IS NULL) on the geography tables:
     * existing active rows are skipped and soft-deleted rows do not block inserts.
     *
     * @param  array<int, array<string, mixed>>  $validRows  Output of bulkValidate().
     * @param  string  $level  province|district|sub_district|village
     */
    public function bulkCommit(array $validRows, string $level, ?callable $onProgress = null): int
    {
        $this->assertLevel($level);
        $config = $this->levelConfig($level);

        if ($validRows === []) {
            throw new InvalidArgumentException('There are no valid rows to import.');
        }

        $model = $config['model'];
        $now = now();

        $count = 0;
        $chunk = [];

        DB::transaction(function () use ($model, $config, $validRows, $now, &$chunk, &$count, $onProgress): void {
            foreach ($validRows as $row) {
                $record = [
                    $config['parent_column'] => $row[$config['parent_column']],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($row['postal_code'] !== null) {
                    $record['postal_code'] = $row['postal_code'];
                }

                $chunk[] = $record;
                $count++;

                if (count($chunk) >= 500) {
                    $model::insertOrIgnore($chunk);
                    $chunk = [];

                    if ($onProgress) {
                        $onProgress($count);
                    }
                }
            }

            if ($chunk !== []) {
                $model::insertOrIgnore($chunk);

                if ($onProgress) {
                    $onProgress($count);
                }
            }
        });

        return $count;
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
            $columns[] = $this->headerMap[$label] ?? 'unknown_'.count($columns);
        }

        return $columns;
    }

    /**
     * Validate a single data row.
     *
     * @param  array<int, string|float|int|null>  $raw
     * @param  array<int, string>  $columns
     * @param  array<int, array<string, mixed>>  $previous  Already-validated preview rows for duplicate detection.
     * @return array<string, mixed>
     */
    protected function validateRow(array $raw, array $columns, int $lineNumber, string $level, array $previous): array
    {
        $cell = function (string $key) use ($columns, $raw): string|float|int|null {
            $index = array_search($key, $columns, true);

            if ($index === false) {
                return null;
            }

            return $raw[$index] ?? null;
        };
        $text = fn (string|float|int|null $value): string => trim((string) $value);

        $code = $text($cell('code'));
        $name = $text($cell('name'));

        if ($code === '') {
            return $this->errorRow($lineNumber, 'Code is required.');
        }

        if ($name === '') {
            return $this->errorRow($lineNumber, 'Name is required.');
        }

        $parent = $this->resolveParent($text($cell('parent_code')), $text($cell('country_code')), $level);

        if ($parent === null) {
            return $this->errorRow($lineNumber, 'Parent "'.$text($cell('parent_code')).'" could not be found. Import the parent level first.');
        }

        $postalCode = $text($cell('postal_code'));

        if ($level === 'village' && $postalCode === '') {
            return $this->errorRow($lineNumber, 'Postal code is required for villages.');
        }

        if ($postalCode !== '' && ! preg_match('/^\d{5}$/', $postalCode)) {
            return $this->errorRow($lineNumber, 'Postal code must be exactly 5 digits.');
        }

        $config = $this->levelConfig($level);
        $key = $parent->id.'|'.$code;

        foreach ($previous as $row) {
            if (($row['key'] ?? null) === $key) {
                return $this->errorRow($lineNumber, 'Duplicate row for the same parent and code.');
            }
        }

        return [
            'line' => $lineNumber,
            'key' => $key,
            'status' => 'ok',
            'errors' => [],
            'code' => $code,
            'name' => $name,
            'parent_id' => $parent->id,
            'parent_name' => $parent->name,
            'postal_code' => $postalCode ?: null,
            'model' => $config['model'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function errorRow(int $line, string $message): array
    {
        return [
            'line' => $line,
            'key' => null,
            'status' => 'error',
            'errors' => [$message],
            'code' => null,
            'name' => null,
            'parent_id' => null,
            'parent_name' => null,
            'postal_code' => null,
            'model' => null,
        ];
    }

    /**
     * Resolve the parent record for a row by its code.
     */
    protected function resolveParent(string $parentCode, string $countryCode, string $level): ?object
    {
        $config = $this->levelConfig($level);

        if ($level === 'province') {
            if ($countryCode !== '') {
                return Country::query()->where('code', $countryCode)->first();
            }

            return Country::query()->where('code', 'IDN')->first()
                ?? Country::query()->first();
        }

        if ($parentCode === '') {
            return null;
        }

        return $config['parent']::query()->where('code', $parentCode)->first();
    }

    /**
     * Resolve a parent id from a preloaded code map, setting an error message
     * when the parent cannot be found.
     *
     * @param  array<string, int>  $parentMap
     */
    protected function resolveParentId(string $parentCode, string $countryCode, string $level, array $parentMap, ?int $defaultCountryId, ?string &$error): ?int
    {
        if ($level === 'province') {
            $id = $countryCode !== '' ? ($parentMap[$countryCode] ?? null) : $defaultCountryId;

            if ($id === null) {
                $error = 'Country "'.($countryCode ?: 'IDN').'" could not be found. Import the country first.';
            }

            return $id;
        }

        if ($parentCode === '') {
            $error = 'Parent code is required.';

            return null;
        }

        $id = $parentMap[$parentCode] ?? null;

        if ($id === null) {
            $error = 'Parent "'.$parentCode.'" could not be found. Import the parent level first.';
        }

        return $id;
    }

    /**
     * @return array{model: class-string, parent: class-string, parent_column: string, has_postal_code: bool}
     */
    protected function levelConfig(string $level): array
    {
        return match ($level) {
            'province' => ['model' => Province::class, 'parent' => Country::class, 'parent_column' => 'country_id', 'has_postal_code' => false],
            'district' => ['model' => District::class, 'parent' => Province::class, 'parent_column' => 'province_id', 'has_postal_code' => false],
            'sub_district' => ['model' => SubDistrict::class, 'parent' => District::class, 'parent_column' => 'district_id', 'has_postal_code' => false],
            'village' => ['model' => Village::class, 'parent' => SubDistrict::class, 'parent_column' => 'sub_district_id', 'has_postal_code' => true],
            default => throw new InvalidArgumentException('Unknown geography level "'.$level.'".'),
        };
    }

    protected function assertLevel(string $level): void
    {
        if (! in_array($level, ['province', 'district', 'sub_district', 'village'], true)) {
            throw new InvalidArgumentException('Unknown geography level "'.$level.'".');
        }
    }
}
