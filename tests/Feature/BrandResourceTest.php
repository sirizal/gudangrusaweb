<?php

use App\Enums\BrandStatus;
use App\Filament\Products\Resources\Brands\Pages\CreateBrand;
use App\Filament\Products\Resources\Brands\Pages\EditBrand;
use App\Filament\Products\Resources\Brands\Pages\ListBrands;
use App\Models\Brand;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAs(User::factory()->create());
    filament()->setCurrentPanel('products');
});

it('renders the brand list page', function () {
    Brand::factory()->count(3)->create();

    Livewire::test(ListBrands::class)
        ->assertSuccessful();
});

it('renders brand fields on the table', function () {
    $brand = Brand::factory()->create();

    Livewire::test(ListBrands::class)
        ->assertCanSeeTableRecords([$brand])
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('status')
        ->assertTableColumnExists('is_featured');
});

it('can create a brand', function () {
    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => 'Bosch',
            'slug' => 'bosch',
            'status' => BrandStatus::Active->value,
            'is_featured' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('brands', [
        'name' => 'Bosch',
        'slug' => 'bosch',
        'status' => BrandStatus::Active->value,
        'is_featured' => true,
    ]);
});

it('can update a brand', function () {
    $brand = Brand::factory()->create();

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->fillForm([
            'name' => 'Makita',
            'slug' => 'makita',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('brands', [
        'name' => 'Makita',
        'slug' => 'makita',
    ]);
});

it('can delete a brand', function () {
    $brand = Brand::factory()->create();

    Livewire::test(EditBrand::class, ['record' => $brand->getRouteKey()])
        ->callAction(DeleteAction::class)
        ->assertNotified();

    $this->assertDatabaseMissing('brands', [
        'id' => $brand->id,
    ]);
});

it('validates the name is required', function () {
    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
        ]);
});
