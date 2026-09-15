<?php

namespace App\Services\Products;

use App\Enums\BrandStatus;
use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OpenSpout\Common\Exception\UnsupportedTypeException;
use OpenSpout\Reader\Common\Creator\ReaderFactory;

class CatalogImportService
{
    /**
     * Header labels that map to the internal import column names.
     *
     * @var array<string, string>
     */
    protected array $headerMap = [
        'name' => 'name',
        'code' => 'code',
        'symbol' => 'symbol',
        'slug' => 'slug',
        'sku' => 'sku',
        'website' => 'website',
        'country' => 'country_of_origin',
        'country_of_origin' => 'country_of_origin',
        'status' => 'status',
        'is_featured' => 'is_featured',
        'is_active' => 'is_active',
        'sort_order' => 'sort_order',
        'unspsc' => 'unspsc',
        'parent_code' => 'parent_code',
        'parent' => 'parent_name',
        'parent_name' => 'parent_name',
        'description' => 'description',
        'brand' => 'brand',
        'brand_name' => 'brand',
        'brand_code' => 'brand',
        'category' => 'category',
        'category_name' => 'category',
        'category_code' => 'category',
        'unit' => 'unit',
        'unit_name' => 'unit',
        'unit_code' => 'unit',
        'price' => 'price',
        'list_price' => 'list_price',
        'quantity_on_hand' => 'quantity_on_hand',
        'qty' => 'quantity_on_hand',
    ];

    /**
     * Fast bulk validation of a CSV/XLSX file for one catalog entity type.
     *
     * @param  string  $type  unit|brand|category|product
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     valid: int,
     *     invalid: int,
     *     errors: array<int, array{line: int, key: string, message: string}>,
     * }
     */
    public function bulkValidate(string $path, string $type): array
    {
        $this->assertType($type);

        $rows = $this->readRows($path);

        if ($rows === []) {
            throw new InvalidArgumentException('The file does not contain any data rows.');
        }

        $columns = $this->mapHeaders(array_shift($rows));

        $maps = $this->referenceMaps($type);

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

            $error = null;
            $record = $this->buildRecord($type, $cell, $text, $maps, $error);
            $record['line'] = $line;

            if ($error === null) {
                $key = $record['key'];

                if (isset($seen[$key])) {
                    $error = 'Duplicate '.$type.' in the same file.';
                } else {
                    $seen[$key] = true;
                }
            }

            if ($error !== null) {
                $errors[] = ['line' => $line, 'key' => $text($cell('name')) ?: $text($cell('code')) ?: $text($cell('sku')), 'message' => $error];

                continue;
            }

            $validRows[] = $record;
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
     * @param  array<int, array<string, mixed>>  $validRows  Output of bulkValidate().
     * @param  string  $type  unit|brand|category|product
     */
    public function bulkCommit(array $validRows, string $type, ?callable $onProgress = null): int
    {
        $this->assertType($type);

        if ($validRows === []) {
            throw new InvalidArgumentException('There are no valid rows to import.');
        }

        $count = 0;

        DB::transaction(function () use ($validRows, $type, &$count, $onProgress): void {
            foreach ($validRows as $row) {
                $this->upsert($type, $row);
                $count++;

                if ($onProgress && $count % 100 === 0) {
                    $onProgress($count);
                }
            }

            if ($onProgress) {
                $onProgress($count);
            }
        });

        return $count;
    }

    /**
     * @return array<string, array<string, int>>
     */
    protected function referenceMaps(string $type): array
    {
        $maps = ['brand' => [], 'category' => [], 'unit' => [], 'parent' => []];

        if (in_array($type, ['product'], true)) {
            $maps['brand'] = Brand::query()->pluck('id', 'slug')->all()
                + Brand::query()->pluck('id', 'name')->all();
            $maps['category'] = Category::query()->pluck('id', 'slug')->all()
                + Category::query()->pluck('id', 'name')->all()
                + Category::query()->pluck('id', 'code')->all();
            $maps['unit'] = Unit::query()->pluck('id', 'code')->all()
                + Unit::query()->pluck('id', 'name')->all();
        }

        if ($type === 'category') {
            $maps['parent'] = Category::query()->pluck('id', 'code')->all()
                + Category::query()->pluck('id', 'name')->all();
        }

        return $maps;
    }

    /**
     * @param  array<string, array<string, int>>  $maps
     * @return array<string, mixed>
     */
    protected function buildRecord(string $type, callable $cell, callable $text, array $maps, ?string &$error): array
    {
        return match ($type) {
            'unit' => $this->buildUnit($cell, $text, $error),
            'brand' => $this->buildBrand($cell, $text, $error),
            'category' => $this->buildCategory($cell, $text, $maps, $error),
            'product' => $this->buildProduct($cell, $text, $maps, $error),
            default => throw new InvalidArgumentException('Unknown catalog type "'.$type.'".'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildUnit(callable $cell, callable $text, ?string &$error): array
    {
        $name = $text($cell('name'));
        $code = Str::upper($text($cell('code')));

        if ($name === '') {
            $error = 'Name is required.';
        } elseif ($code === '') {
            $error = 'Code is required.';
        } elseif (Unit::where('code', $code)->exists()) {
            $error = 'Unit code "'.$code.'" already exists.';
        }

        return [
            'key' => $code,
            'name' => $name,
            'code' => $code,
            'symbol' => $text($cell('symbol')) ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildBrand(callable $cell, callable $text, ?string &$error): array
    {
        $name = $text($cell('name'));
        $slug = $text($cell('slug')) ?: Str::slug($name);
        $status = $text($cell('status')) ?: BrandStatus::Active->value;

        if ($name === '') {
            $error = 'Name is required.';
        } elseif ($slug === '') {
            $error = 'Name must produce a slug.';
        } elseif (Brand::where('slug', $slug)->exists()) {
            $error = 'Brand "'.$name.'" already exists.';
        } elseif (! in_array($status, [BrandStatus::Active->value, BrandStatus::Inactive->value], true)) {
            $error = 'Status must be active or inactive.';
        }

        return [
            'key' => $slug,
            'name' => $name,
            'slug' => $slug,
            'website' => $text($cell('website')) ?: null,
            'country_of_origin' => $text($cell('country_of_origin')) ?: null,
            'status' => $status,
            'is_featured' => $this->boolValue($text($cell('is_featured'))),
            'description' => $text($cell('description')) ?: null,
        ];
    }

    /**
     * @param  array<string, array<string, int>>  $maps
     * @return array<string, mixed>
     */
    protected function buildCategory(callable $cell, callable $text, array $maps, ?string &$error): array
    {
        $name = $text($cell('name'));
        $code = Str::upper($text($cell('code')));
        $slug = $text($cell('slug')) ?: Str::slug($name);
        $parentCode = $text($cell('parent_code'));
        $parentName = $text($cell('parent_name'));
        $parent = $parentCode !== '' ? ($maps['parent'][$parentCode] ?? null) : ($parentName !== '' ? ($maps['parent'][$parentName] ?? null) : null);

        if ($name === '') {
            $error = 'Name is required.';
        } elseif ($code === '') {
            $error = 'Code is required.';
        } elseif (Category::where('code', $code)->exists()) {
            $error = 'Category code "'.$code.'" already exists.';
        } elseif (($parentCode !== '' || $parentName !== '') && $parent === null) {
            $error = 'Parent category "'.($parentCode ?: $parentName).'" could not be found.';
        }

        return [
            'key' => $code,
            'name' => $name,
            'slug' => $slug,
            'code' => $code,
            'unspsc' => $text($cell('unspsc')) ?: null,
            'parent_id' => $parent,
            'description' => $text($cell('description')) ?: null,
            'is_active' => $this->boolValue($text($cell('is_active')), true),
            'sort_order' => (int) ($text($cell('sort_order')) ?: 0),
        ];
    }

    /**
     * @param  array<string, array<string, int>>  $maps
     * @return array<string, mixed>
     */
    protected function buildProduct(callable $cell, callable $text, array $maps, ?string &$error): array
    {
        $name = $text($cell('name'));
        $sku = Str::upper($text($cell('sku')));
        $slug = $text($cell('slug')) ?: Str::slug($name);

        $brand = $this->resolveRef($text($cell('brand')), $maps['brand']);
        $category = $this->resolveRef($text($cell('category')), $maps['category']);
        $unit = $this->resolveRef($text($cell('unit')), $maps['unit']);

        $status = $text($cell('status')) ?: ProductStatus::Draft->value;
        $price = $text($cell('price'));
        $listPrice = $text($cell('list_price'));
        $quantity = $text($cell('quantity_on_hand'));

        if ($name === '') {
            $error = 'Name is required.';
        } elseif ($sku === '') {
            $error = 'SKU is required.';
        } elseif (Product::where('sku', $sku)->exists()) {
            $error = 'SKU "'.$sku.'" already exists.';
        } elseif ($text($cell('brand')) !== '' && $brand === null) {
            $error = 'Brand "'.$text($cell('brand')).'" could not be found.';
        } elseif ($text($cell('category')) !== '' && $category === null) {
            $error = 'Category "'.$text($cell('category')).'" could not be found.';
        } elseif ($text($cell('unit')) === '' || $unit === null) {
            $error = $text($cell('unit')) === '' ? 'Unit is required.' : 'Unit "'.$text($cell('unit')).'" could not be found.';
        } elseif ($price !== '' && ! is_numeric($price)) {
            $error = 'Price must be numeric.';
        } elseif ($listPrice !== '' && ! is_numeric($listPrice)) {
            $error = 'List price must be numeric.';
        } elseif ($quantity !== '' && ! is_numeric($quantity)) {
            $error = 'Quantity must be numeric.';
        } elseif (! in_array($status, [ProductStatus::Draft->value, ProductStatus::Active->value, ProductStatus::Inactive->value], true)) {
            $error = 'Status must be draft, active or inactive.';
        }

        return [
            'key' => $sku,
            'name' => $name,
            'slug' => $slug,
            'sku' => $sku,
            'description' => $text($cell('description')) ?: null,
            'brand_id' => $brand,
            'category_id' => $category,
            'unit_id' => $unit,
            'price' => $price === '' ? 0 : (float) $price,
            'list_price' => $listPrice === '' ? null : (float) $listPrice,
            'quantity_on_hand' => $quantity === '' ? 0 : (int) $quantity,
            'status' => $status,
            'is_featured' => $this->boolValue($text($cell('is_featured'))),
        ];
    }

    /**
     * @param  array<string, int>  $map
     */
    protected function resolveRef(string $value, array $map): ?int
    {
        if ($value === '') {
            return null;
        }

        return $map[$value] ?? null;
    }

    protected function boolValue(string $value, bool $default = false): bool
    {
        $value = strtolower(trim($value));

        if (in_array($value, ['1', 'true', 'yes', 'y'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no', 'n'], true)) {
            return false;
        }

        return $default;
    }

    protected function upsert(string $type, array $row): void
    {
        switch ($type) {
            case 'unit':
                Unit::firstOrCreate(['code' => $row['code']], [
                    'name' => $row['name'],
                    'symbol' => $row['symbol'],
                ]);

                break;
            case 'brand':
                Brand::firstOrCreate(['slug' => $row['slug']], [
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'website' => $row['website'],
                    'country_of_origin' => $row['country_of_origin'],
                    'status' => $row['status'],
                    'is_featured' => $row['is_featured'],
                ]);

                break;
            case 'category':
                Category::firstOrCreate(['code' => $row['code']], [
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'unspsc' => $row['unspsc'],
                    'parent_id' => $row['parent_id'],
                    'description' => $row['description'],
                    'is_active' => $row['is_active'],
                    'sort_order' => $row['sort_order'],
                ]);

                break;
            case 'product':
                Product::firstOrCreate(['sku' => $row['sku']], [
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'description' => $row['description'],
                    'brand_id' => $row['brand_id'],
                    'category_id' => $row['category_id'],
                    'unit_id' => $row['unit_id'],
                    'price' => $row['price'],
                    'list_price' => $row['list_price'],
                    'quantity_on_hand' => $row['quantity_on_hand'],
                    'status' => $row['status'],
                    'is_featured' => $row['is_featured'],
                ]);

                break;
        }
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

    protected function assertType(string $type): void
    {
        if (! in_array($type, ['unit', 'brand', 'category', 'product'], true)) {
            throw new InvalidArgumentException('Unknown catalog type "'.$type.'".');
        }
    }
}
