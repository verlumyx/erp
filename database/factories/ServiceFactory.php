<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Service\Models\Service>
 */
class ServiceFactory extends Factory
{
    protected $model = \App\Modules\Service\Models\Service::class;

    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = 'SER'.str_pad((string) (++self::$sequence), 6, '0', STR_PAD_LEFT);

        return [
            'company_id' => Company::factory(),
            'code' => $code,
            'name' => fake()->unique()->words(2, true),
            'logo_url' => fake()->optional()->imageUrl(),
            'max_profiles' => fake()->numberBetween(1, 6),
            'active' => true,
        ];
    }

    /**
     * Indicate that the service is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'active' => false,
        ]);
    }
}
