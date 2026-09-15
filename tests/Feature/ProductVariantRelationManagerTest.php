<?php

use App\Filament\Products\Resources\Products\Pages\EditProduct;
use App\Filament\Products\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\CreateAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAs(User::factory()->create());
    filament()->setCurrentPanel('products');
});

it('renders the variants relation manager', function () {
    $product = Product::factory()->create();

    $product->variants()->create([
        'sku' => 'V-SKU-001',
        'model_number' => 'MOD-001',
        'size' => 'M10',
        'color' => 'Black',
        'unit' => 'pcs',
        'selling_price' => 75000,
        'available_stock' => 30,
        'is_active' => true,
    ]);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->assertSuccessful();
});

it('can create a variant through the relation manager', function () {
    $product = Product::factory()->create();

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction(CreateAction::class, data: [
            'model_number' => 'MOD-002',
            'size' => '12 mm',
            'color' => 'Silver',
            'unit' => 'pcs',
            'customer_sku' => 'CUST-ABC',
            'metadata' => ['material' => 'Steel'],
            'selling_price' => 120000,
            'available_stock' => 15,
            'leadtime' => 7,
            'is_active' => true,
        ])
        ->assertHasNoTableActionErrors();

    $variant = $product->variants()->first();

    $this->assertDatabaseHas('product_variants', [
        'product_id' => $product->id,
        'model_number' => 'MOD-002',
        'selling_price' => 120000,
        'available_stock' => 15,
        'unit' => 'pcs',
        'customer_sku' => 'CUST-ABC',
    ]);

    $this->assertMatchesRegularExpression('/^S\d{7}$/', $variant->sku);

    $this->assertDatabaseHas('product_variants', [
        'metadata' => json_encode(['material' => 'Steel']),
    ]);
});
