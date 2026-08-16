<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Warehouse\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = \App\Modules\Warehouse\Models\Warehouse::class;

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
            'code' => 'BOD'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'name' => 'Bodega '.ucfirst(fake()->unique()->word()).' '.$sequence,
            'type' => 'main',
            'address' => fake()->optional()->streetAddress(),
            'phone' => fake()->optional()->numerify('##########'),
            'city' => fake()->optional()->city(),
            'responsible_user_id' => null,
            'is_default' => 'no',
            'allows_negative_stock' => 'no',
            'uses_locations' => 'no',
            'is_sales_available' => 'yes',
            'notes' => fake()->optional()->sentence(),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the warehouse manages internal locations.
     */
    public function usesLocations(): static
    {
        return $this->state(fn (array $attributes): array => [
            'uses_locations' => 'yes',
        ]);
    }

    /**
     * Indicate that the warehouse is the default one of its company.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => 'yes',
        ]);
    }

    /**
     * Indicate that the warehouse is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
