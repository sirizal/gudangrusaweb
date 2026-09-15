<?php

use App\Enums\BrandStatus;
use App\Models\Brand;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('applies default status and featured values', function () {
    $brand = Brand::factory()->create([
        'status' => BrandStatus::Active,
        'is_featured' => false,
    ]);

    expect($brand->status)->toBe(BrandStatus::Active)
        ->and($brand->is_featured)->toBeFalse();
});

it('casts status to enum', function () {
    $brand = Brand::factory()->create();

    expect($brand->status)->toBeInstanceOf(BrandStatus::class);
});

it('scope active returns only active brands', function () {
    Brand::factory()->create();
    Brand::factory()->inactive()->create();

    expect(Brand::active()->count())->toBe(1);
});

it('scope featured returns only featured brands', function () {
    Brand::factory()->featured()->create();
    Brand::factory()->create(['is_featured' => false]);

    expect(Brand::featured()->count())->toBe(1);
});

it('requires a unique slug', function () {
    Brand::factory()->create(['slug' => 'brand-a']);

    $this->expectException(QueryException::class);

    Brand::factory()->create(['slug' => 'brand-a']);
});
