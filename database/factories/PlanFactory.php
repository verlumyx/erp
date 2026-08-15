<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Service\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Plan\Models\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = \App\Modules\Plan\Models\Plan::class;

    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'PLA'.str_pad((string) (++self::$sequence), 6, '0', STR_PAD_LEFT);

        return [
            'company_id' => Company::factory(),
            'code' => $code,
            'service_id' => Service::factory(),
            'name' => fake()->words(2, true),
            'capacity' => fake()->randomElement(['profile', 'full_account']),
            'duration_days' => fake()->randomElement([30, 60, 90, 180, 365]),
            'sale_price' => fake()->randomFloat(2, 1, 200),
            'roi_target_pct' => fake()->randomFloat(2, 0, 80),
            'active' => true,
        ];
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active' => false,
        ]);
    }
}
