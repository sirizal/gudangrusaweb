<?php

namespace Database\Factories;

use App\Enums\CompanyDocumentType;
use App\Models\Company;
use App\Models\CompanyDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyDocument>
 */
class CompanyDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'document_type' => fake()->randomElement(CompanyDocumentType::cases())->value,
            'document_number' => fake()->numerify('###/###/####'),
            'name' => fake()->words(4, true),
            'start_date' => fake()->date(),
            'end_date' => fake()->optional()->date(),
            'file_path' => null,
        ];
    }
}
