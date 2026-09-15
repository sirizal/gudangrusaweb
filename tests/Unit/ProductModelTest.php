<?php

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a product with default status draft', function () {
    $product = Product::factory()->create(['status' => ProductStatus::Draft, 'is_featured' => false]);

    expect($product->status)->toBe(ProductStatus::Draft)
        ->and($product->is_featured)->toBeFalse();
});

it('belongs to brand, category and unit', function () {
    $brand = Brand::factory()->create();
    $category = Category::factory()->create();
    $unit = Unit::factory()->create();

    $product = Product::factory()->create([
        'brand_id' => $brand->id,
        'category_id' => $category->id,
        'unit_id' => $unit->id,
    ]);

    expect($product->brand->is($brand))->toBeTrue()
        ->and($product->category->is($category))->toBeTrue()
        ->and($product->unit->is($unit))->toBeTrue();
});

it('orders images by sort order', function () {
    $product = Product::factory()->create();

    $product->images()->create(['image_path' => 'a.jpg', 'sort_order' => 2]);
    $product->images()->create(['image_path' => 'b.jpg', 'sort_order' => 1]);

    expect($product->images->pluck('image_path')->values()->all())->toBe(['b.jpg', 'a.jpg']);
});

it('enforces a unique sku', function () {
    Product::factory()->create(['sku' => 'SKU-001']);

    $this->expectException(QueryException::class);

    Product::factory()->create(['sku' => 'SKU-001']);
});

it('casts metadata to an array', function () {
    $product = Product::factory()->create(['metadata' => ['voltage' => '220V']]);

    expect($product->metadata)->toBe(['voltage' => '220V']);
});

it('soft deletes a product', function () {
    $product = Product::factory()->create();
    $product->delete();

    expect(Product::find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();
});
