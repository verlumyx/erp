<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Client\Models\ClientContact>
 */
class ClientContactFactory extends Factory
{
    protected $model = \App\Modules\Client\Models\ClientContact::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'client_id' => Client::factory(),
            'name' => fake()->name(),
            'position' => fake()->optional()->jobTitle(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->numerify('+58 4## ### ####'),
            'is_primary' => 'no',
            'status' => 'active',
        ];
    }

    /**
     * The primary contact of the client: only one per client.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => 'yes',
        ]);
    }
}
