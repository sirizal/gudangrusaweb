<?php

use App\Filament\Products\Resources\Units\Pages\CreateUnit;
use App\Filament\Products\Resources\Units\Pages\EditUnit;
use App\Filament\Products\Resources\Units\Pages\ListUnits;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAs(User::factory()->create());
    filament()->setCurrentPanel('products');
});

it('renders the unit list page', function () {
    Unit::factory()->count(3)->create();

    Livewire::test(ListUnits::class)
        ->assertSuccessful();
});

it('can create a unit', function () {
    Livewire::test(CreateUnit::class)
        ->fillForm([
            'name' => 'Pieces',
            'code' => 'PCS',
            'symbol' => 'pcs',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('units', [
        'name' => 'Pieces',
        'code' => 'PCS',
        'symbol' => 'pcs',
    ]);
});

it('validates the unit code is unique', function () {
    Unit::factory()->create(['code' => 'PCS']);

    Livewire::test(CreateUnit::class)
        ->fillForm([
            'name' => 'Pieces',
            'code' => 'PCS',
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'unique']);
});

it('can update a unit', function () {
    $unit = Unit::factory()->create();

    Livewire::test(EditUnit::class, ['record' => $unit->getRouteKey()])
        ->fillForm([
            'symbol' => 'set',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('units', [
        'id' => $unit->id,
        'symbol' => 'set',
    ]);
});
