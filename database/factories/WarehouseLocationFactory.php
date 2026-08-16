<?php

namespace Database\Factories;

use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\WarehouseLocation\Models\WarehouseLocation>
 */
class WarehouseLocationFactory extends Factory
{
    protected $model = \App\Modules\WarehouseLocation\Models\WarehouseLocation::class;

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
            'warehouse_id' => Warehouse::factory()->usesLocations(),
            'company_id' => fn (array $attributes): ?string => Warehouse::find($attributes['warehouse_id'])?->company_id,
            'code' => 'UBI'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'parent_id' => null,
            'name' => 'Ubicación '.$sequence,
            'location_code' => sprintf('A-%02d-%02d', $sequence % 100, $sequence % 50),
            'type' => 'shelf',
            'capacity' => 0,
            'is_default' => 'no',
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the location is the default one of its warehouse.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => 'yes',
        ]);
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
