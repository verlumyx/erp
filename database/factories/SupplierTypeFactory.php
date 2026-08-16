<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SupplierType\Models\SupplierType>
 */
class SupplierTypeFactory extends Factory
{
    protected $model = \App\Modules\SupplierType\Models\SupplierType::class;

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
            'code' => 'TPR'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'name' => 'Tipo de proveedor '.$sequence,
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the supplier type is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
