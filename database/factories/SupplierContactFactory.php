<?php

namespace Database\Factories;

use App\Modules\Supplier\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Supplier\Models\SupplierContact>
 */
class SupplierContactFactory extends Factory
{
    protected $model = \App\Modules\Supplier\Models\SupplierContact::class;

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
            'name' => fake()->name(),
            'position' => fake()->optional()->jobTitle(),
            'email' => fake()->optional()->safeEmail(),
            'phone' => fake()->optional()->numerify('+58 4## ### ####'),
            'is_primary' => 'no',
            'status' => 'active',
        ];
    }

    /**
     * The primary contact of the supplier: only one per supplier.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_primary' => 'yes',
        ]);
    }
}
