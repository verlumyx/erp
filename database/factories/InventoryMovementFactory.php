<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\InventoryMovement\Models\InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    protected $model = \App\Modules\InventoryMovement\Models\InventoryMovement::class;

    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * En producción el kardex solo lo escribe
     * `InventoryMovementRegisterService`; la factory existe para montar
     * historia ya cerrada en los tests de listado.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = ++self::$sequence;

        return [
            'item_id' => Item::factory(),
            'company_id' => fn (array $attributes): ?string => Item::find($attributes['item_id'])?->company_id,
            'warehouse_id' => Warehouse::factory(),
            'location_id' => fn (array $attributes): string => WarehouseLocation::factory()->create([
                'warehouse_id' => $attributes['warehouse_id'],
                'company_id' => Warehouse::find($attributes['warehouse_id'])?->company_id,
            ])->id,
            'code' => 'MOV'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'movement_date' => now()->toDateTimeString(),
            'type' => 'in',
            'origin_type' => 'entry',
            'origin_id' => (string) Str::uuid7(),
            'origin_line_id' => null,
            'lot_id' => null,
            'serial_id' => null,
            'quantity' => 1,
            'unit_cost' => 0,
            'total_cost' => 0,
            'balance_quantity' => 0,
            'balance_cost' => 0,
            'balance_value' => 0,
            'reversal_of_id' => null,
            'status' => 'active',
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the movement discharges stock instead of loading it.
     */
    public function outbound(string $type = 'out'): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
        ]);
    }

    /**
     * Indicate the quantity and unit cost the movement carries.
     */
    public function valued(float $quantity, float $unitCost): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => round($quantity * $unitCost, 2),
        ]);
    }

    /**
     * Indicate that the movement already has its counter-entry.
     */
    public function reversed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'reversed',
        ]);
    }
}
