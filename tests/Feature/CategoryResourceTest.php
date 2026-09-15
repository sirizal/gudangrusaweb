<?php

use App\Filament\Products\Resources\Categories\Pages\CreateCategory;
use App\Filament\Products\Resources\Categories\Pages\EditCategory;
use App\Filament\Products\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAs(User::factory()->create());
    filament()->setCurrentPanel('products');
});

it('renders the category list page', function () {
    Category::factory()->count(3)->create();

    Livewire::test(ListCategories::class)
        ->assertSuccessful();
});

it('can create a category with catalog fields', function () {
    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Power Tools',
            'slug' => 'power-tools',
            'code' => 'CAT-PT',
            'unspsc' => '27111506',
            'is_active' => true,
            'sort_order' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'Power Tools',
        'slug' => 'power-tools',
        'code' => 'CAT-PT',
        'unspsc' => '27111506',
        'is_active' => true,
    ]);
});

it('validates the category code is unique', function () {
    Category::factory()->create(['code' => 'CAT-PT']);

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Power Tools',
            'code' => 'CAT-PT',
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'unique']);
});

it('can set a parent category', function () {
    $parent = Category::factory()->create();

    Livewire::test(CreateCategory::class)
        ->fillForm([
            'name' => 'Cordless',
            'slug' => 'cordless',
            'code' => 'CAT-CDL',
            'parent_id' => $parent->id,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'name' => 'Cordless',
        'parent_id' => $parent->id,
    ]);
});

it('can update a category', function () {
    $category = Category::factory()->create();

    Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
        ->fillForm([
            'unspsc' => '27111506',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'unspsc' => '27111506',
    ]);
});
