<?php

namespace Database\Factories;

use App\Models\SubDistrict;
use App\Models\Village;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Village>
 */
class VillageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sub_district_id' => SubDistrict::factory(),
            'code' => (string) fake()->unique()->numberBetween(1101011001, 9999999999),
            'name' => fake()->unique()->citySuffix(),
            'postal_code' => (string) fake()->numberBetween(10000, 99999),
            'is_active' => true,
        ];
    }
}
