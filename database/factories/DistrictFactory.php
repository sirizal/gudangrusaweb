<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<District>
 */
class DistrictFactory extends Factory
{
    public function definition(): array
    {
        return [
            'province_id' => Province::factory(),
            'code' => (string) fake()->unique()->numberBetween(1101, 9909),
            'name' => fake()->unique()->city(),
            'is_active' => true,
        ];
    }
}
