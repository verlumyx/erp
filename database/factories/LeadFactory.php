<?php

namespace Database\Factories;

use App\Modules\Lead\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Lead\Models\Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('+1##########'),
            'status' => Lead::STATUS_PENDING,
        ];
    }

    /**
     * Indicate that the lead has been reviewed.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => Lead::STATUS_REVIEWED,
        ]);
    }
}
