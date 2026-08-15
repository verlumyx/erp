<?php

namespace Database\Factories;

use App\Modules\Account\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Account\Models\Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = \App\Modules\Account\Models\Profile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'number' => fake()->numberBetween(1, 6),
            'pin' => null,
            'status' => 'available',
            'notes' => null,
        ];
    }

    public function occupied(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'occupied',
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'maintenance',
        ]);
    }
}
