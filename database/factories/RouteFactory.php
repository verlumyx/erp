<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Route\Models\Route>
 */
class RouteFactory extends Factory
{
    protected $model = \App\Modules\Route\Models\Route::class;

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
            'code' => 'RUT'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'name' => 'Ruta '.$sequence,
            'description' => null,
            'type' => 'delivery',
            'warehouse_id' => null,
            'driver_id' => null,
            'salesperson_id' => null,
            'vehicle_plate' => null,
            /** Sin capacidad declarada: la planificación no compara contra nada. */
            'vehicle_capacity_weight' => 0,
            'vehicle_capacity_volume' => 0,
            'frequency' => 'weekly',
            'weekdays' => ['mon', 'wed', 'fri'],
            'zone' => 'Zona Norte',
            'city' => 'Caracas',
            'estimated_duration_minutes' => 0,
            'estimated_distance_km' => 0,
            'notes' => null,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /** Una ruta con vehículo declarado: la carga del día tiene que caber. */
    public function withCapacity(float $weight = 100, float $volume = 50): static
    {
        return $this->state(fn (array $attributes): array => [
            'vehicle_capacity_weight' => $weight,
            'vehicle_capacity_volume' => $volume,
            'vehicle_plate' => 'AB123CD',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
