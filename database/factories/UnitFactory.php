<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $units = [
            ['name' => 'Pieces', 'code' => 'PCS', 'symbol' => 'pcs'],
            ['name' => 'Box', 'code' => 'BOX', 'symbol' => 'box'],
            ['name' => 'Set', 'code' => 'SET', 'symbol' => 'set'],
            ['name' => 'Pair', 'code' => 'PR', 'symbol' => 'pair'],
            ['name' => 'Roll', 'code' => 'RL', 'symbol' => 'roll'],
            ['name' => 'Meter', 'code' => 'M', 'symbol' => 'm'],
        ];

        return $this->faker->unique()->randomElement($units);
    }
}
