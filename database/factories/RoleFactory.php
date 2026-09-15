<?php

namespace Database\Factories;

use App\Enums\Role as RoleEnum;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $role = fake()->randomElement(RoleEnum::cases());

        return [
            'code' => $role->value,
            'name' => $role->getLabel(),
            'description' => fake()->sentence(),
        ];
    }

    public function withCode(string $code): static
    {
        return $this->state(fn (): array => [
            'code' => $code,
            'name' => Str::headline($code),
        ]);
    }
}
