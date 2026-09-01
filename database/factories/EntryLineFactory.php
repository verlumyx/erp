<?php

namespace Database\Factories;

use App\Modules\Entry\Models\Entry;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Entry\Models\EntryLine>
 */
class EntryLineFactory extends Factory
{
    protected $model = \App\Modules\Entry\Models\EntryLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = 10;
        $unitPrice = 25;

        return [
            'company_id' => null,
            'entry_id' => Entry::factory(),
            'line_number' => 1,
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'sourceable_type' => null,
            'sourceable_id' => null,
            'location_id' => null,
            'lot_number' => null,
            'lot_id' => null,
            'expires_at' => null,
            'serial_numbers' => null,
            'quantity' => $quantity,
            'base_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_id' => null,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'withholding_percent' => 0,
            'withholding_amount' => 0,
            'subtotal' => $quantity * $unitPrice,
            'total' => $quantity * $unitPrice,
            'received_quantity' => $quantity,
            'rejected_quantity' => 0,
            'unit_cost' => $unitPrice,
            'landed_cost' => $unitPrice,
            'rejection_reason' => null,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
