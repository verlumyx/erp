<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\ExchangeRate\Models\ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    protected $model = \App\Modules\ExchangeRate\Models\ExchangeRate::class;

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
            'code' => 'TAS'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'currency' => 'USD',
            'rate_date' => now()->subDays($sequence)->toDateString(),
            'rate' => fake()->randomFloat(4, 1, 100),
            'type' => 'legal',
            'source' => fake()->optional()->company(),
            'description' => fake()->optional()->sentence(),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the exchange rate is a manually loaded one.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'manual',
        ]);
    }

    /**
     * Indicate that the exchange rate is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
