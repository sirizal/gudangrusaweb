<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => null,
            'model_number' => strtoupper(fake()->unique()->bothify('MOD-####-##')),
            'size' => fake()->optional()->randomElement(['S', 'M', 'L', 'XL', '10 mm', '12 mm', '15 mm', 'M8', 'M10', 'M12']),
            'color' => fake()->optional()->randomElement(['Red', 'Blue', 'Black', 'White', 'Green', 'Silver']),
            'image_path' => fake()->optional()->imageUrl(600, 600),
            'unit' => fake()->randomElement(['pcs', 'box', 'set', 'pair', 'meter']),
            'metadata' => fake()->optional()->randomElement([
                ['material' => 'Steel'],
                ['voltage' => '220V'],
                ['capacity' => '5 kg'],
                ['finish' => 'Zinc plated'],
            ]),
            'customer_sku' => fake()->optional()->bothify('CUST-###-????'),
            'available_stock' => fake()->numberBetween(0, 500),
            'leadtime' => fake()->optional()->numberBetween(1, 60),
            'selling_price' => fake()->randomFloat(2, 1000, 2000000),
            'is_active' => true,
            'supply_city' => fake()->optional()->randomElement(['Jakarta', 'Surabaya', 'Bandung', 'Semarang', 'Medan']),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn (): array => [
            'available_stock' => 0,
        ]);
    }
}
