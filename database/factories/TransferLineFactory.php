<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\Transfer\Models\Transfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Transfer\Models\TransferLine>
 */
class TransferLineFactory extends Factory
{
    protected $model = \App\Modules\Transfer\Models\TransferLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = 1;
        /** El traslado no pone precio: el importe de la línea es su costo. */
        $unitCost = 60;

        return [
            'company_id' => null,
            'transfer_id' => Transfer::factory(),
            'line_number' => 1,
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'origin_location_id' => null,
            'destination_location_id' => null,
            'lot_id' => null,
            'serial_id' => null,
            'quantity' => $quantity,
            'base_quantity' => $quantity,
            'unit_price' => $unitCost,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_id' => null,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'withholding_percent' => 0,
            'withholding_amount' => 0,
            'subtotal' => $quantity * $unitCost,
            'total' => $quantity * $unitCost,
            'sent_quantity' => 0,
            'received_quantity' => 0,
            'difference_quantity' => 0,
            'unit_cost' => $unitCost,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
