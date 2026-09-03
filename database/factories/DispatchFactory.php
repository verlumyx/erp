<?php

namespace Database\Factories;

use App\Modules\Client\Models\Client;
use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Dispatch\Models\Dispatch>
 */
class DispatchFactory extends Factory
{
    protected $model = \App\Modules\Dispatch\Models\Dispatch::class;

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
            'code' => 'DES'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'recipient_type' => Client::MORPH_ALIAS,
            'recipient_id' => Client::factory(),
            'sourceable_type' => null,
            'sourceable_id' => null,
            'client_address_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'route_id' => null,
            'route_stop_id' => null,
            'dispatch_date' => now()->toDateString(),
            'delivery_date' => null,
            'driver_id' => null,
            'vehicle_plate' => null,
            'carrier' => null,
            'tracking_number' => null,
            'total_quantity' => 0,
            'total_weight' => 0,
            'total_volume' => 0,
            'total_cost' => 0,
            'delivery_status' => 'pending',
            'received_by_name' => null,
            'received_by_document' => null,
            'signature_path' => null,
            'evidence_path' => null,
            'latitude' => null,
            'longitude' => null,
            'rejection_reason' => null,
            'cancelled_at' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * A confirmed dispatch: the goods already left the warehouse and are on
     * their way, so it can no longer be edited.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
            'delivery_status' => 'in_transit',
        ]);
    }

    /** A dispatch the client already received in full. */
    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'completed',
            'delivery_status' => 'delivered',
            'delivery_date' => now()->toDateString(),
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
