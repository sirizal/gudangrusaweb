<?php

use App\Filament\Products\Pages\ImportCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAs(User::factory()->create());
});

it('allows authenticated users into the products panel', function () {
    $user = User::query()->first();

    expect($user->canAccessPanel(filament()->getPanel('products')))->toBeTrue();
});

it('renders the products dashboard and every resource page', function () {
    get('/catalog')->assertOk();

    foreach ([
        'brands',
        'categories',
        'products',
        'units',
    ] as $slug) {
        get("/catalog/{$slug}")->assertOk();
        get("/catalog/{$slug}/create")->assertOk();
    }
});

it('renders the catalog import page with native Filament controls', function () {
    get('/catalog/import-catalog')->assertOk();

    $html = Livewire::test(ImportCatalog::class)->html();

    expect($html)
        ->toContain('fi-select-input')
        ->toContain('fi-fo-file-upload');
});
