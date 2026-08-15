<?php

namespace Database\Factories;

use App\Modules\Account\Models\Account;
use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Account\Models\AccountRenewal>
 */
class AccountRenewalFactory extends Factory
{
    protected $model = \App\Modules\Account\Models\AccountRenewal::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->toDateString();
        $end = now()->addDays(30)->toDateString();

        return [
            'company_id' => Company::factory(),
            'account_id' => Account::factory(),
            'type' => 'renewal',
            'amount' => fake()->randomFloat(2, 1, 100),
            'period_start' => $start,
            'period_end' => $end,
            'paid_at' => $start,
            'notes' => null,
            'created_by' => null,
        ];
    }

    /**
     * Renovación asociada a una account (y su compañía) concretas.
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes): array => [
            'company_id' => $account->company_id,
            'account_id' => $account->id,
        ]);
    }

    public function purchase(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'purchase',
        ]);
    }
}
