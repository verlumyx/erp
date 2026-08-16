<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Client\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = \App\Modules\Client\Models\Client::class;

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
            'code' => 'CLI'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'client_type_id' => null,
            'price_list_id' => null,
            'name' => fake()->name(),
            'legal_name' => null,
            'document_type' => 'V',
            'document_number' => str_pad((string) (10000000 + $sequence), 8, '0', STR_PAD_LEFT),
            'phone' => fake()->optional()->numerify('+58 4## ### ####'),
            'mobile' => null,
            'email' => fake()->unique()->safeEmail(),
            'address' => fake()->optional()->streetAddress(),
            'city' => null,
            'state' => null,
            'country' => null,
            'payment_term_days' => 0,
            'credit_limit' => 0,
            'credit_blocked' => 'no',
            'current_balance' => 0,
            'advance_balance' => 0,
            'discount_percent' => 0,
            'salesperson_id' => null,
            'route_id' => null,
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the client is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }

    /**
     * A client that still owes money: it cannot be deactivated.
     */
    public function withBalance(float $balance = 150.00): static
    {
        return $this->state(fn (array $attributes): array => [
            'current_balance' => $balance,
        ]);
    }
}
