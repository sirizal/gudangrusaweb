<?php

namespace Database\Factories;

use App\Enums\BrandStatus;
use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'logo_path' => null,
            'website' => fake()->url(),
            'country_of_origin' => fake()->randomElement(['ID', 'JP', 'KR', 'DE', 'US']),
            'status' => BrandStatus::Active,
            'is_featured' => fake()->boolean(20),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => BrandStatus::Inactive,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => [
            'is_featured' => true,
        ]);
    }
}
