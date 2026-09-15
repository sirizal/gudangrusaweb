<?php

use App\Filament\Products\Resources\Products\Pages\CreateProduct;
use App\Filament\Products\Resources\Products\Pages\EditProduct;
use App\Filament\Products\Resources\Products\Pages\ListProducts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
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

it('renders the product list page', function () {
    Product::factory()->count(3)->create();

    Livewire::test(ListProducts::class)
        ->assertSuccessful();
});

it('can create a product', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Cordless Drill 18V',
            'slug' => 'cordless-drill-18v',
            'sku' => 'DRL-18V-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'price' => 2500000,
            'list_price' => 3000000,
            'quantity_on_hand' => 25,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', [
        'name' => 'Cordless Drill 18V',
        'sku' => 'DRL-18V-001',
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'quantity_on_hand' => 25,
        'status' => 'active',
    ]);
});

it('validates the product sku is unique', function () {
    Product::factory()->create(['sku' => 'DRL-18V-001']);
    $unit = Unit::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => 'Drill',
            'slug' => 'drill',
            'sku' => 'DRL-18V-001',
            'unit_id' => $unit->id,
            'status' => 'active',
        ])
        ->call('create')
        ->assertHasFormErrors(['sku' => 'unique']);
});

it('can update a product', function () {
    $product = Product::factory()->create();

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->fillForm([
            'price' => 2200000,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'price' => 2200000,
    ]);
});
