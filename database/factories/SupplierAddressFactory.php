<?php

namespace Database\Factories;

use App\Modules\Supplier\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Supplier\Models\SupplierAddress>
 */
class SupplierAddressFactory extends Factory
{
    protected $model = \App\Modules\Supplier\Models\SupplierAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'supplier_id' => Supplier::factory(),
            'type' => 'billing',
            'address' => fake()->streetAddress(),
            'city' => fake()->optional()->city(),
            'state' => null,
            'country' => null,
            'is_default' => 'no',
            'status' => 'active',
        ];
    }

    /**
     * The suggested address for its type.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => 'yes',
        ]);
    }
}
