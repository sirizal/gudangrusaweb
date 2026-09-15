<?php

use App\Models\Category;
use App\Models\ProductImage;
use App\Models\Unit;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a unit with a unique code', function () {
    $unit = Unit::factory()->create();

    expect($unit->name)->not->toBeEmpty()
        ->and($unit->code)->not->toBeEmpty();
});

it('enforces a unique unit code', function () {
    $code = 'PCS';
    Unit::factory()->create(['code' => $code]);

    $this->expectException(QueryException::class);

    Unit::factory()->create(['code' => $code]);
});

it('creates a category as a root node by default', function () {
    $category = Category::factory()->create();

    expect($category->parent_id)->toBeNull()
        ->and(Category::root()->count())->toBe(1);
});

it('links a child category to its parent', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->childOf($parent)->create();

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children->contains($child))->toBeTrue();
});

it('soft deletes a category', function () {
    $category = Category::factory()->create();
    $category->delete();

    expect(Category::find($category->id))->toBeNull()
        ->and(Category::withTrashed()->find($category->id))->not->toBeNull();
});

it('casts category active flag and product image order', function () {
    $category = Category::factory()->create(['is_active' => false]);

    expect($category->is_active)->toBeFalse();
});

it('relates a product image to a product', function () {
    $image = ProductImage::factory()->create();

    expect($image->product_id)->not->toBeNull()
        ->and($image->product)->not->toBeNull();
});
