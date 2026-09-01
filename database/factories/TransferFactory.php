<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Transfer\Models\Transfer>
 */
class TransferFactory extends Factory
{
    protected $model = \App\Modules\Transfer\Models\Transfer::class;

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
            'code' => 'TRA'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'origin_warehouse_id' => Warehouse::factory(),
            'destination_warehouse_id' => Warehouse::factory(),
            /** Sin bodega de tránsito el traslado es inmediato: un solo paso. */
            'transit_warehouse_id' => null,
            'transfer_date' => now()->toDateString(),
            'expected_date' => null,
            'received_date' => null,
            'reason' => 'restock',
            'reason_detail' => null,
            'driver_id' => null,
            'vehicle_plate' => null,
            'route_id' => null,
            'total_quantity' => 0,
            'total_cost' => 0,
            'transfer_status' => 'pending',
            'sent_by' => null,
            'received_by' => null,
            'cancelled_at' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /** Un traslado inmediato ya confirmado: la mercancía llegó en el acto. */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
            'transfer_status' => 'received',
            'received_date' => $attributes['transfer_date'] ?? now()->toDateString(),
        ]);
    }

    /** Un traslado en dos pasos con la mercancía todavía en la calle. */
    public function inTransit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
            'transfer_status' => 'in_transit',
            'transit_warehouse_id' => Warehouse::factory(),
            'received_date' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}
