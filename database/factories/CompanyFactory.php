<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        $code = Str::upper(Str::substr(fake()->unique()->companySuffix().fake()->unique()->randomNumber(3), 0, 10));

        return [
            'code' => $code,
            'name' => fake()->company(),
            'is_active' => true,
            'address' => fake()->streetAddress(),
            'postal_code' => (string) fake()->numberBetween(10000, 99999),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'website' => 'https://'.fake()->domainName(),
            'country_id' => Country::factory(),
            'npwp' => fake()->numerify('##.###.###.#-###.###'),
            'nib' => fake()->numerify('##############'),
            'is_pkp' => fake()->boolean(),
            'tax_office' => 'KPP Pratama '.fake()->city(),
            'tax_registration_date' => fake()->date(),
        ];
    }
}
