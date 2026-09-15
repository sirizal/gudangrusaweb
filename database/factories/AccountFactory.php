<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(AccountType::cases());

        return [
            'company_id' => Company::factory(),
            'parent_id' => null,
            'account_code' => fake()->unique()->numerify('####'),
            'account_name' => Str::title(fake()->words(2, true)),
            'account_name_en' => null,
            'account_type' => $type,
            'account_sub_type' => null,
            'normal_balance' => $type->defaultNormalBalance(),
            'level' => 1,
            'is_group' => false,
            'is_postable' => true,
            'is_active' => true,
            'description' => null,
        ];
    }

    public function group(): static
    {
        return $this->state(fn (): array => [
            'is_group' => true,
            'is_postable' => false,
        ]);
    }

    public function postable(): static
    {
        return $this->state(fn (): array => [
            'is_group' => false,
            'is_postable' => true,
        ]);
    }

    public function ofType(AccountType $type): static
    {
        return $this->state(fn (): array => [
            'account_type' => $type,
            'normal_balance' => $type->defaultNormalBalance(),
        ]);
    }

    public function withCode(string $code): static
    {
        return $this->state(fn (): array => [
            'account_code' => $code,
        ]);
    }

    public function withParent(Account $parent): static
    {
        return $this->state(fn (): array => [
            'parent_id' => $parent->id,
            'level' => $parent->level + 1,
        ]);
    }
}
