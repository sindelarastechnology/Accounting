<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->numerify('####-##-###'),
            'name' => $this->faker->word(),
            'category' => $this->faker->randomElement(['asset', 'liability', 'equity', 'revenue', 'cogs', 'expense']),
            'normal_balance' => 'debit',
            'parent_id' => null,
            'is_header' => false,
            'is_active' => true,
            'description' => null,
        ];
    }
}
