<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Tax\Models\Tax>
 */
class TaxFactory extends Factory
{
    protected $model = \App\Modules\Tax\Models\Tax::class;

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
            'code' => 'IMP'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'name' => 'Impuesto '.$sequence,
            'description' => fake()->optional()->sentence(),
            'percentage' => fake()->randomFloat(2, 0, 30),
            'has_withholding' => 'no',
            'withholding_percentage' => 0,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the tax also practises a withholding.
     */
    public function withWithholding(float $percentage = 75): static
    {
        return $this->state(fn (array $attributes): array => [
            'has_withholding' => 'yes',
            'withholding_percentage' => $percentage,
        ]);
    }

    /**
     * Indicate that the tax is exempt (0%).
     */
    public function exempt(): static
    {
        return $this->state(fn (array $attributes): array => [
            'percentage' => 0,
        ]);
    }

    /**
     * Indicate that the tax is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
