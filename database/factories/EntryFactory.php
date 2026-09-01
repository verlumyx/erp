<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\User\Models\User;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Entry\Models\Entry>
 */
class EntryFactory extends Factory
{
    protected $model = \App\Modules\Entry\Models\Entry::class;

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
            'code' => 'ENT'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'supplier_id' => Supplier::factory(),
            'sourceable_type' => null,
            'sourceable_id' => null,
            'warehouse_id' => Warehouse::factory(),
            'entry_date' => now()->toDateString(),
            'entry_type' => 'purchase',
            'supplier_document' => null,
            'carrier' => null,
            'tracking_number' => null,
            'received_by' => null,
            'inspected_by' => null,
            'inspection_status' => 'pending',
            'currency' => 'USD',
            'exchange_rate' => 1,
            'base_currency' => 'USD',
            'base_exchange_rate' => 1,
            'total_quantity' => 0,
            'freight_amount' => 0,
            'other_charges' => 0,
            'total_cost' => 0,
            'is_invoiced' => 'no',
            'cancelled_at' => null,
            'notes' => null,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }

    /**
     * A confirmed entry: the goods are already in the warehouse, so it can no
     * longer be edited.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'confirmed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /** Carga del inventario inicial: sin proveedor y sin documento origen. */
    public function initial(): static
    {
        return $this->state(fn (array $attributes): array => [
            'entry_type' => 'initial',
            'supplier_id' => null,
        ]);
    }
}
