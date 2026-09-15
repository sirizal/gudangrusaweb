<?php

namespace Database\Factories;

use App\Models\District;
use App\Models\SubDistrict;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubDistrict>
 */
class SubDistrictFactory extends Factory
{
    public function definition(): array
    {
        return [
            'district_id' => District::factory(),
            'code' => (string) fake()->unique()->numberBetween(110101, 999999),
            'name' => fake()->unique()->streetName(),
            'is_active' => true,
        ];
    }
}
