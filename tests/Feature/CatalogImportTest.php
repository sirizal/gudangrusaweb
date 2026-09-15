<?php

use App\Filament\Products\Pages\ImportCatalog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Services\Products\CatalogImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAs(User::factory()->create());
    filament()->setCurrentPanel('products');
});

function writeCatalogCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'cat-').'.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row, ',', '"', '\\');
    }

    fclose($handle);

    return $path;
}

it('validates and commits units', function () {
    $path = writeCatalogCsv([
        ['name', 'code', 'symbol'],
        ['Piece', 'PCS', 'pcs'],
        ['Box', 'BOX', 'box'],
    ]);

    $preview = app(CatalogImportService::class)->bulkValidate($path, 'unit');

    expect($preview['valid'])->toBe(2)
        ->and($preview['invalid'])->toBe(0);

    app(CatalogImportService::class)->bulkCommit($preview['rows'], 'unit');

    expect(Unit::where('code', 'PCS')->firstOrFail()->name)->toBe('Piece')
        ->and(Unit::where('code', 'BOX')->exists())->toBeTrue();

    app(CatalogImportService::class)->bulkCommit($preview['rows'], 'unit');

    expect(Unit::where('code', 'PCS')->count())->toBe(1);

    unlink($path);
});

it('validates and commits brands', function () {
    $path = writeCatalogCsv([
        ['name', 'website', 'country_of_origin', 'status', 'is_featured'],
        ['Acme', 'https://acme.test', 'US', 'active', '1'],
    ]);

    $preview = app(CatalogImportService::class)->bulkValidate($path, 'brand');

    expect($preview['valid'])->toBe(1);

    app(CatalogImportService::class)->bulkCommit($preview['rows'], 'brand');

    $brand = Brand::where('slug', 'acme')->firstOrFail();

    expect($brand->country_of_origin)->toBe('US')
        ->and($brand->status->value)->toBe('active')
        ->and($brand->is_featured)->toBeTrue();

    unlink($path);
});

it('validates and commits categories with a parent', function () {
    Category::factory()->create(['code' => 'ELEC', 'name' => 'Electronics', 'slug' => 'electronics']);

    $path = writeCatalogCsv([
        ['name', 'code', 'parent_code'],
        ['Laptops', 'LAP', 'ELEC'],
    ]);

    $preview = app(CatalogImportService::class)->bulkValidate($path, 'category');

    expect($preview['valid'])->toBe(1);

    app(CatalogImportService::class)->bulkCommit($preview['rows'], 'category');

    $category = Category::where('code', 'LAP')->firstOrFail();

    expect($category->parent->code)->toBe('ELEC')
        ->and($category->is_active)->toBeTrue();

    unlink($path);
});

it('validates and commits products with resolved references', function () {
    $brand = Brand::factory()->create(['slug' => 'acme', 'name' => 'Acme']);
    $category = Category::factory()->create(['code' => 'ELEC', 'name' => 'Electronics', 'slug' => 'electronics']);
    $unit = Unit::factory()->create(['code' => 'PCS', 'name' => 'Piece']);

    $path = writeCatalogCsv([
        ['name', 'sku', 'brand', 'category', 'unit', 'price', 'quantity_on_hand', 'status'],
        ['Laptop 14', 'LAP-001', 'Acme', 'ELEC', 'PCS', '15000000', '5', 'active'],
    ]);

    $preview = app(CatalogImportService::class)->bulkValidate($path, 'product');

    expect($preview['valid'])->toBe(1);

    app(CatalogImportService::class)->bulkCommit($preview['rows'], 'product');

    $product = Product::where('sku', 'LAP-001')->firstOrFail();

    expect($product->brand_id)->toBe($brand->id)
        ->and($product->category_id)->toBe($category->id)
        ->and($product->unit_id)->toBe($unit->id)
        ->and((float) $product->price)->toBe(15000000.0)
        ->and($product->status->value)->toBe('active');

    unlink($path);
});

it('flags missing required fields and unknown references', function () {
    $path = writeCatalogCsv([
        ['name', 'sku', 'unit', 'price'],
        ['No Sku', '', 'PCS', '100'],
        ['Bad Unit', 'SKU-1', 'NOPE', '100'],
    ]);

    $preview = app(CatalogImportService::class)->bulkValidate($path, 'product');

    expect($preview['valid'])->toBe(0)
        ->and($preview['invalid'])->toBe(2);

    $messages = array_column($preview['errors'], 'message');

    expect(implode(' ', $messages))
        ->toContain('SKU is required')
        ->toContain('Unit "NOPE" could not be found');

    unlink($path);
});

it('flags duplicate keys in the same file', function () {
    $path = writeCatalogCsv([
        ['name', 'code'],
        ['Piece', 'PCS'],
        ['Pieces', 'PCS'],
    ]);

    $preview = app(CatalogImportService::class)->bulkValidate($path, 'unit');

    expect($preview['valid'])->toBe(1)
        ->and($preview['invalid'])->toBe(1)
        ->and($preview['errors'][0]['message'])->toContain('Duplicate');

    unlink($path);
});

it('previews and commits through the import page', function () {
    $file = UploadedFile::fake()->createWithContent('units.csv', "name,code,symbol\nPiece,PCS,pcs\nBox,BOX,box\n");

    $component = Livewire::test(ImportCatalog::class)
        ->set('data.type', 'unit')
        ->set('data.file', $file)
        ->call('preview');

    $component
        ->assertSet('previewSummary.valid', 2)
        ->assertSet('previewSummary.invalid', 0);

    $component->call('import');

    expect(Unit::where('code', 'PCS')->exists())->toBeTrue()
        ->and(Unit::where('code', 'BOX')->exists())->toBeTrue();
});

it('rejects an unknown catalog type', function () {
    expect(fn () => app(CatalogImportService::class)->bulkValidate('/tmp/nope.csv', 'widget'))
        ->toThrow(InvalidArgumentException::class, 'Unknown catalog type "widget".');
});
