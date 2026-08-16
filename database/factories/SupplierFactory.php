<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Supplier\Models\Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = \App\Modules\Supplier\Models\Supplier::class;

    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = ++self::$sequence;

        return [
            'company_id' => Company::factory(),
            'code' => 'PRO'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'supplier_type_id' => null,
            'name' => fake()->company(),
            'legal_name' => null,
            'document_type' => 'J',
            'document_number' => str_pad((string) (10000000 + $sequence), 8, '0', STR_PAD_LEFT),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->optional()->numerify('+58 2## ### ####'),
            'mobile' => null,
            'website' => null,
            'address' => fake()->optional()->streetAddress(),
            'city' => null,
            'state' => null,
            'country' => null,
            'currency' => 'USD',
            'payment_term_days' => 0,
            'credit_limit' => 0,
            'current_balance' => 0,
            'advance_balance' => 0,
            'lead_time_days' => 0,
            'notes' => null,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the supplier is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }

    /**
     * A supplier that still owes money: it cannot be deactivated.
     */
    public function withBalance(float $balance = 150.00): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_balance' => $balance,
        ]);
    }
}
