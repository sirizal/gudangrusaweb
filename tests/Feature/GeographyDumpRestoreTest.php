<?php

use App\Models\Country;
use App\Models\District;
use App\Models\Province;
use App\Models\SubDistrict;
use App\Models\Village;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(GeographySeeder::class);

    $country = Country::where('code', 'IDN')->firstOrFail();
    $province = Province::factory()->create(['country_id' => $country->id]);
    $district = District::factory()->create(['province_id' => $province->id]);
    $subDistrict = SubDistrict::factory()->create(['district_id' => $district->id]);
    Village::factory()->count(3)->create(['sub_district_id' => $subDistrict->id]);
});

function geographyCounts(): array
{
    return [
        Country::count(),
        Province::count(),
        District::count(),
        SubDistrict::count(),
        Village::count(),
    ];
}

it('dumps and restores the geography tables round-trip', function () {
    $before = geographyCounts();
    $path = sys_get_temp_dir().'/geo-dump-'.uniqid().'.json';

    $this->artisan('geography:dump', ['file' => $path])->assertSuccessful();

    foreach (['villages', 'sub_districts', 'districts', 'provinces', 'countries'] as $table) {
        DB::table($table)->delete();
    }

    expect(Village::count())->toBe(0);

    $this->artisan('geography:restore', ['file' => $path])->assertSuccessful();

    expect(geographyCounts())->toBe($before);

    $village = Village::query()->latest('id')->first();

    expect($village->postal_code)->not->toBeNull()
        ->and($village->subDistrict->district->province->country->code)->toBe('IDN');

    unlink($path);
});

it('restores exactly the dump contents when run with --fresh', function () {
    $path = sys_get_temp_dir().'/geo-dump-'.uniqid().'.json';

    $this->artisan('geography:dump', ['file' => $path])->assertSuccessful();

    $dumped = geographyCounts();

    Village::factory()->create(['sub_district_id' => SubDistrict::query()->firstOrFail()->id]);

    $this->artisan('geography:restore', ['file' => $path, '--fresh' => true])->assertSuccessful();

    expect(geographyCounts())->toBe($dumped);

    unlink($path);
});

it('restore is idempotent without --fresh', function () {
    $path = sys_get_temp_dir().'/geo-dump-'.uniqid().'.json';

    $this->artisan('geography:dump', ['file' => $path])->assertSuccessful();

    $before = Village::count();

    $this->artisan('geography:restore', ['file' => $path])->assertSuccessful();

    expect(Village::count())->toBe($before);

    unlink($path);
});

it('reports a missing dump file', function () {
    $this->artisan('geography:restore', ['file' => '/tmp/does-not-exist-geo.json'])
        ->assertExitCode(2);
});
