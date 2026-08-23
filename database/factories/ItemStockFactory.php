<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\ItemStock\Models\ItemStock>
 */
class ItemStockFactory extends Factory
{
    protected $model = \App\Modules\ItemStock\Models\ItemStock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'warehouse_id' => Warehouse::factory(),
            'location_id' => fn (array $attributes): string => WarehouseLocation::factory()->create([
                'warehouse_id' => $attributes['warehouse_id'],
                'company_id' => Warehouse::find($attributes['warehouse_id'])?->company_id,
            ])->id,
            'company_id' => fn (array $attributes): ?string => Warehouse::find($attributes['warehouse_id'])?->company_id,
            'lot_id' => null,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'incoming_quantity' => 0,
            'available_quantity' => 0,
            'average_cost' => 0,
            'total_value' => 0,
            'last_movement_at' => null,
            'status' => 'active',
        ];
    }

    /**
     * Indicate the balance holds the given quantity at the given unit cost.
     */
    public function withBalance(float $quantity, float $averageCost = 0): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity' => $quantity,
            'available_quantity' => $quantity - (float) ($attributes['reserved_quantity'] ?? 0),
            'average_cost' => $averageCost,
            'total_value' => round($quantity * $averageCost, 2),
        ]);
    }

    /**
     * Indicate that the balance is no longer tracked.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
