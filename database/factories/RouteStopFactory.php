<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Route\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Route\Models\RouteStop>
 */
class RouteStopFactory extends Factory
{
    protected $model = \App\Modules\Route\Models\RouteStop::class;

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
            'route_id' => Route::factory(),
            'company_id' => fn (array $attributes): ?string => Route::find($attributes['route_id'])?->company_id,
            'client_id' => Client::factory(),
            'client_address_id' => null,
            'stop_date' => now()->toDateString(),
            'sequence' => $sequence,
            'estimated_arrival' => null,
            'actual_arrival' => null,
            'actual_departure' => null,
            'stop_status' => 'pending',
            'skip_reason' => null,
            'latitude' => null,
            'longitude' => null,
            'status' => 'active',
        ];
    }

    /** La visita ya se hizo: la parada está cerrada. */
    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'stop_status' => 'completed',
            'actual_arrival' => now(),
            'actual_departure' => now()->addMinutes(20),
        ]);
    }

    /** No se pudo visitar: el motivo es obligatorio. */
    public function skipped(string $reason = 'El local estaba cerrado'): static
    {
        return $this->state(fn (array $attributes): array => [
            'stop_status' => 'skipped',
            'skip_reason' => $reason,
        ]);
    }
}
