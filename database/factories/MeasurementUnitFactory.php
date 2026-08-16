<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\MeasurementUnit\Models\MeasurementUnit>
 */
class MeasurementUnitFactory extends Factory
{
    protected $model = \App\Modules\MeasurementUnit\Models\MeasurementUnit::class;

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
            'code' => 'UOM'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'name' => 'Unidad '.$sequence,
            'description' => fake()->optional()->sentence(),
            'abbreviation' => 'u'.$sequence,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the measurement unit is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
