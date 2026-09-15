<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-###-????')),
            'description' => fake()->paragraph(),
            'brand_id' => Brand::factory(),
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'price' => fake()->randomFloat(2, 1000, 1000000),
            'list_price' => fake()->optional()->randomFloat(2, 1000, 1000000),
            'quantity_on_hand' => fake()->numberBetween(0, 500),
            'status' => ProductStatus::Active,
            'is_featured' => fake()->boolean(15),
            'metadata' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::Draft,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => ProductStatus::Inactive,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => [
            'quantity_on_hand' => 0,
        ]);
    }
}
