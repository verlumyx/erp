<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Route\Models\Route;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Route\Models\RouteClient>
 */
class RouteClientFactory extends Factory
{
    protected $model = \App\Modules\Route\Models\RouteClient::class;

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
            'sequence' => $sequence,
            'status' => 'active',
        ];
    }
}
