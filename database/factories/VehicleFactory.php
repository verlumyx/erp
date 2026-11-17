<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Vehicle\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Vehicle\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

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
            'code' => 'CAM'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'plate' => strtoupper(fake()->bothify('?##???#')),
            'name' => 'Camión '.ucfirst(fake()->word()).' '.$sequence,
            'brand' => fake()->randomElement(['Chevrolet', 'Ford', 'Iveco', 'Mack', 'Toyota']),
            'model' => fake()->randomElement(['NPR', 'Cargo 1721', 'FVR', 'Super Duty', 'Hilux']),
            'year' => fake()->numberBetween(2010, 2024),
            'vehicle_type' => fake()->randomElement(['pickup', 'cava_350', 'truck', 'heavy_truck']),
            'capacity_weight' => fake()->randomFloat(2, 1000, 10000),
            'capacity_volume' => fake()->randomFloat(2, 5, 40),
            'notes' => fake()->optional()->sentence(),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }
}
