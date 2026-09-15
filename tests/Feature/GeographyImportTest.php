<?php

use App\Enums\Role;
use App\Filament\Geography\Pages\ImportGeography;
use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\Role as RoleModel;
use App\Models\SubDistrict;
use App\Models\User;
use App\Models\Village;
use App\Services\Geography\GeographyImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(GeographySeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(
        RoleModel::where('code', Role::SuperAdmin->value)->firstOrCreate(
            ['code' => Role::SuperAdmin->value],
            ['name' => Role::SuperAdmin->getLabel()],
        ),
    );

    actingAs($this->admin);
});

function writeCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'geo-').'.csv';
    $handle = fopen($path, 'w');

    foreach ($rows as $row) {
        fputcsv($handle, $row, ',', '"', '\\');
    }

    fclose($handle);

    return $path;
}

it('parses a valid province CSV into a preview without mutating data', function () {
    $path = writeCsv([
        ['code', 'name'],
        ['32', 'Jawa Barat'],
        ['33', 'Jawa Tengah'],
    ]);

    $preview = app(GeographyImportService::class)->parse($path, 'province');

    expect($preview['valid'])->toBe(2)
        ->and($preview['invalid'])->toBe(0)
        ->and($preview['rows'][0]['code'])->toBe('32')
        ->and($preview['rows'][0]['parent_id'])->toBe(Country::where('code', 'IDN')->firstOrFail()->id);

    unlink($path);

    expect(Province::count())->toBe(1);
});

it('commits parsed provinces as an upsert', function () {
    $path = writeCsv([
        ['code', 'name'],
        ['32', 'Jawa Barat'],
        ['33', 'Jawa Tengah'],
    ]);

    $preview = app(GeographyImportService::class)->parse($path, 'province');
    app(GeographyImportService::class)->commit($preview, 'province');

    expect(Province::where('code', '32')->firstOrFail()->name)->toBe('Jawa Barat')
        ->and(Province::where('code', '33')->firstOrFail()->name)->toBe('Jawa Tengah');

    app(GeographyImportService::class)->commit($preview, 'province');

    expect(Province::where('code', '32')->count())->toBe(1);

    unlink($path);
});

it('flags a missing parent level during the preview', function () {
    $path = writeCsv([
        ['code', 'name', 'parent_code'],
        ['31.75', 'Kota Jakarta Timur', '99'],
    ]);

    $preview = app(GeographyImportService::class)->parse($path, 'district');

    expect($preview['valid'])->toBe(0)
        ->and($preview['invalid'])->toBe(1)
        ->and($preview['rows'][0]['errors'][0])->toContain('Parent "99" could not be found');

    unlink($path);
});

it('validates the postal code for villages during the preview', function () {
    $subDistrict = SubDistrict::query()->firstOrFail();

    $path = writeCsv([
        ['code', 'name', 'parent_code', 'postal_code'],
        ['31.74.07.2001', 'Lebak Bulus', $subDistrict->code, '1234'],
    ]);

    $preview = app(GeographyImportService::class)->parse($path, 'village');

    expect($preview['valid'])->toBe(0)
        ->and($preview['invalid'])->toBe(1)
        ->and($preview['rows'][0]['errors'][0])->toContain('Postal code must be exactly 5 digits');

    unlink($path);
});

it('flags duplicate rows in the same file', function () {
    $province = Province::query()->firstOrFail();

    $path = writeCsv([
        ['code', 'name', 'parent_code'],
        ['31.71', 'Kota Jakarta Pusat', $province->code],
        ['31.71', 'Kota Jakarta Pusat', $province->code],
    ]);

    $preview = app(GeographyImportService::class)->parse($path, 'district');

    expect($preview['valid'])->toBe(1)
        ->and($preview['invalid'])->toBe(1)
        ->and($preview['rows'][1]['errors'][0])->toContain('Duplicate');

    unlink($path);
});

it('commits a full village chain import and stores the postal code', function () {
    $province = Province::query()->firstOrFail();
    $district = District::query()->firstOrFail();
    $subDistrict = SubDistrict::query()->firstOrFail();

    $path = writeCsv([
        ['code', 'name', 'parent_code', 'postal_code'],
        ['31.74.07.2001', 'Lebak Bulus', $subDistrict->code, '12440'],
        ['31.74.07.2002', 'Pondok Indah', $subDistrict->code, '12310'],
    ]);

    $preview = app(GeographyImportService::class)->parse($path, 'village');

    expect($preview['valid'])->toBe(2);

    app(GeographyImportService::class)->commit($preview, 'village');

    $village = Village::where('code', '31.74.07.2001')->firstOrFail();

    expect($village->postal_code)->toBe('12440')
        ->and($village->subDistrict->is($subDistrict))->toBeTrue()
        ->and($village->subDistrict->district->is($district))->toBeTrue()
        ->and($village->subDistrict->district->province->is($province))->toBeTrue();

    unlink($path);
});

it('bulk-imports a full CSV via bulkValidate and bulkCommit', function () {
    $district = District::query()->where('code', '31.74')->firstOrFail();
    $path = writeCsv([
        ['code', 'name', 'parent_code'],
        ['31.74.99', 'Kecamatan A', $district->code],
        ['31.74.98', 'Kecamatan B', $district->code],
    ]);

    $preview = app(GeographyImportService::class)->bulkValidate($path, 'sub_district');

    expect($preview['valid'])->toBe(2)
        ->and($preview['invalid'])->toBe(0)
        ->and($preview['errors'])->toBe([]);

    $count = app(GeographyImportService::class)->bulkCommit($preview['rows'], 'sub_district');

    expect($count)->toBe(2)
        ->and(SubDistrict::where('code', '31.74.99')->exists())->toBeTrue()
        ->and(SubDistrict::where('code', '31.74.98')->exists())->toBeTrue();

    app(GeographyImportService::class)->bulkCommit($preview['rows'], 'sub_district');

    expect(SubDistrict::where('code', '31.74.99')->count())->toBe(1);

    unlink($path);
});

it('bulkValidate flags missing parents and bad postal codes', function () {
    $path = writeCsv([
        ['code', 'name', 'parent_code', 'postal_code'],
        ['11.99.99.2001', 'Orphan village', '99.99.99', '1234'],
    ]);

    $preview = app(GeographyImportService::class)->bulkValidate($path, 'village');

    expect($preview['valid'])->toBe(0)
        ->and($preview['invalid'])->toBe(1)
        ->and($preview['errors'][0]['message'])->toContain('Parent "99.99.99" could not be found');

    unlink($path);
});

it('allows re-creating a soft-deleted record with the same code', function () {
    $province = Province::query()->firstOrFail();
    $district = District::factory()->create(['province_id' => $province->id, 'code' => '99.99']);
    $district->delete();

    $recreated = District::create([
        'province_id' => $province->id,
        'code' => '99.99',
        'name' => 'Recreated district',
        'is_active' => true,
    ]);

    expect($recreated->exists)->toBeTrue()
        ->and(District::where('code', '99.99')->count())->toBe(1);
});

it('builds the five-level relationship chain from the seeder', function () {
    $village = Village::query()->firstOrFail();

    expect($village->subDistrict->district->province->country)
        ->not->toBeNull()
        ->and($village->subDistrict->district->province->country->code)->toBe('IDN')
        ->and($village->subDistrict->district->province->country->provinces->first()->is($village->subDistrict->district->province))->toBeTrue();
});

it('rejects an unknown import level', function () {
    expect(fn () => app(GeographyImportService::class)->parse('/tmp/nonexistent.csv', 'city'))
        ->toThrow(InvalidArgumentException::class, 'Unknown geography level "city".');
});

it('throws when committing an import with no valid rows', function () {
    $path = writeCsv([
        ['code', 'name', 'parent_code'],
        ['99.99', 'Not A District', '99'],
    ]);

    $preview = app(GeographyImportService::class)->parse($path, 'district');

    expect($preview['valid'])->toBe(0);

    app(GeographyImportService::class)->commit($preview, 'district');
})->throws(InvalidArgumentException::class, 'There are no valid rows to import.');

it('previews and commits an uploaded file through the import page', function () {
    $file = UploadedFile::fake()->createWithContent('provinces.csv', "code,name\n32,Jawa Barat\n33,Jawa Tengah\n");

    $component = Livewire::test(ImportGeography::class)
        ->set('data.level', 'province')
        ->set('data.file', $file)
        ->call('preview');

    $component
        ->assertSet('previewSummary.valid', 2)
        ->assertSet('previewSummary.invalid', 0)
        ->assertSet('previewRows.0.code', '32');

    $component->call('import');

    expect(Province::where('code', '32')->firstOrFail()->name)->toBe('Jawa Barat')
        ->and(Province::where('code', '33')->firstOrFail()->name)->toBe('Jawa Tengah');
});
