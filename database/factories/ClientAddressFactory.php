<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Client\Models\ClientAddress>
 */
class ClientAddressFactory extends Factory
{
    protected $model = \App\Modules\Client\Models\ClientAddress::class;

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
            'type' => 'shipping',
            'name' => 'Sucursal '.fake()->city(),
            'address' => fake()->streetAddress(),
            'city' => fake()->optional()->city(),
            'state' => null,
            'country' => null,
            'route_id' => null,
            'latitude' => null,
            'longitude' => null,
            'is_default' => 'no',
            'status' => 'active',
        ];
    }

    /**
     * The address suggested when creating documents: one per type.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => 'yes',
        ]);
    }

    /**
     * A billing address instead of a delivery one.
     */
    public function billing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'billing',
        ]);
    }
}
