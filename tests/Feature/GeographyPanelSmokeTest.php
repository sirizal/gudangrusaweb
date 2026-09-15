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
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GeographySeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->roles()->attach(
        RoleModel::where('code', Role::SuperAdmin->value)->firstOrCreate(
            ['code' => Role::SuperAdmin->value],
            ['name' => Role::SuperAdmin->getLabel()],
        ),
    );

    actingAs($this->admin);
});

it('denies non-super-admin users access to the geography panel', function () {
    $viewer = User::factory()->create();
    $viewer->roles()->attach(
        RoleModel::where('code', Role::Viewer->value)->firstOrCreate(
            ['code' => Role::Viewer->value],
            ['name' => Role::Viewer->getLabel()],
        ),
    );

    actingAs($viewer);

    expect($viewer->canAccessPanel(filament()->getPanel('geography')))->toBeFalse();
    get('/geography')->assertForbidden();
});

it('allows super-admin users access to the geography panel', function () {
    expect($this->admin->canAccessPanel(filament()->getPanel('geography')))->toBeTrue();
    get('/geography')->assertOk();
});

it('renders every geography index and create page', function () {
    foreach ([
        'countries',
        'provinces',
        'districts',
        'sub-districts',
        'villages',
    ] as $slug) {
        get("/geography/{$slug}")->assertOk();
        get("/geography/{$slug}/create")->assertOk();
    }
});

it('renders the geography import page with native Filament controls', function () {
    $html = Livewire::test(ImportGeography::class)->html();

    expect($html)
        ->toContain('fi-select-input')
        ->toContain('fi-fo-file-upload');
});

it('renders every geography edit page', function () {
    $country = Country::query()->firstOrFail();
    $province = Province::query()->firstOrFail();
    $district = District::query()->firstOrFail();
    $subDistrict = SubDistrict::query()->firstOrFail();
    $village = Village::query()->firstOrFail();

    get("/geography/countries/{$country->id}/edit")->assertOk();
    get("/geography/provinces/{$province->id}/edit")->assertOk();
    get("/geography/districts/{$district->id}/edit")->assertOk();
    get("/geography/sub-districts/{$subDistrict->id}/edit")->assertOk();
    get("/geography/villages/{$village->id}/edit")->assertOk();
});
