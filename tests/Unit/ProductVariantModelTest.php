<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to a product', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

    expect($variant->product->is($product))->toBeTrue()
        ->and($product->variants->contains($variant))->toBeTrue();
});

it('stores the unit as free text', function () {
    $variant = ProductVariant::factory()->create(['unit' => 'pcs']);

    expect($variant->unit)->toBe('pcs');
});

it('stores the customer sku as nullable free text', function () {
    $variant = ProductVariant::factory()->create(['customer_sku' => 'CUST-001']);

    expect($variant->customer_sku)->toBe('CUST-001');

    $empty = ProductVariant::factory()->create(['customer_sku' => null]);

    expect($empty->customer_sku)->toBeNull();
});

it('casts metadata to an array', function () {
    $variant = ProductVariant::factory()->create([
        'metadata' => ['material' => 'Steel', 'voltage' => '220V'],
    ]);

    expect($variant->metadata)->toBe(['material' => 'Steel', 'voltage' => '220V']);

    $empty = ProductVariant::factory()->create(['metadata' => null]);

    expect($empty->metadata)->toBeNull();
});

it('stores a unique sku', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'V-SKU-001']);

    expect($variant->sku)->toBe('V-SKU-001');
});

it('auto generates an sku in S followed by seven digits when creating', function () {
    $variant = ProductVariant::factory()->create();

    expect($variant->sku)->toMatch('/^S\d{7}$/');
});

it('auto generates sequential running sku numbers', function () {
    $first = ProductVariant::factory()->create();
    $second = ProductVariant::factory()->create();

    $firstNumber = (int) substr($first->sku, 1);
    $secondNumber = (int) substr($second->sku, 1);

    expect($secondNumber)->toBe($firstNumber + 1);
});

it('does not overwrite an explicit sku', function () {
    $variant = ProductVariant::factory()->create(['sku' => 'CUSTOM-01']);

    expect($variant->sku)->toBe('CUSTOM-01');
});

it('enforces a globally unique variant sku', function () {
    ProductVariant::factory()->create(['sku' => 'V-SKU-001']);

    $this->expectException(QueryException::class);

    ProductVariant::factory()->create(['sku' => 'V-SKU-001']);
});

it('applies default active, stock and price values', function () {
    $variant = ProductVariant::factory()->create();

    expect($variant->is_active)->toBeTrue()
        ->and($variant->available_stock)->toBeGreaterThanOrEqual(0);
});

it('casts selling price and active flag', function () {
    $variant = ProductVariant::factory()->create(['selling_price' => 125000.50]);

    expect($variant->is_active)->toBeBool()
        ->and($variant->selling_price)->toBe('125000.50');
});

it('scope in stock returns only stocked variants', function () {
    ProductVariant::factory()->create(['available_stock' => 10]);
    ProductVariant::factory()->outOfStock()->create();

    expect(ProductVariant::inStock()->count())->toBe(1);
});

it('cascades delete variants when the product is force deleted', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

    $product->forceDelete();

    expect(ProductVariant::withTrashed()->count())->toBe(0);
});

it('soft deletes a variant', function () {
    $variant = ProductVariant::factory()->create();
    $variant->delete();

    expect(ProductVariant::find($variant->id))->toBeNull()
        ->and(ProductVariant::withTrashed()->find($variant->id))->not->toBeNull();
});
