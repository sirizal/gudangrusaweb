<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Database\Seeders\ShopSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(ShopSeeder::class);
});

it('shows the homepage with categories and featured products', function () {
    get('/')
        ->assertOk()
        ->assertSee('Kategori Produk')
        ->assertSee('Produk Pilihan')
        ->assertSee('Perkakas Tangan');
});

it('lists active products on the products page', function () {
    $product = Product::query()->active()->first();

    get('/products')
        ->assertOk()
        ->assertSee($product->name);
});

it('shows a category page including products from child categories', function () {
    $root = Category::query()->where('slug', 'perkakas-tangan')->firstOrFail();
    $childProduct = Product::query()->whereHas('category', fn ($query) => $query->where('parent_id', $root->id))->firstOrFail();

    get("/categories/{$root->slug}")
        ->assertOk()
        ->assertSee($childProduct->name);
});

it('shows the product detail page', function () {
    $product = Product::query()->active()->with('variants')->firstOrFail();

    get("/products/{$product->slug}")
        ->assertOk()
        ->assertSee($product->name);
});

it('returns 404 for an inactive product on the detail page', function () {
    $product = Product::factory()->inactive()->create([
        'name' => 'Produk Rahasia',
        'slug' => 'produk-rahasia',
        'sku' => 'SECRET-01',
        'unit_id' => Unit::query()->first()->id,
    ]);

    get("/products/{$product->slug}")->assertNotFound();
});

it('finds products via search', function () {
    $product = Product::query()->active()->first();

    get('/search?q='.urlencode($product->name))
        ->assertOk()
        ->assertSee($product->name);
});
