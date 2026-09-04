<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SalesReturn\Models\SalesReturnLine>
 */
class SalesReturnLineFactory extends Factory
{
    protected $model = \App\Modules\SalesReturn\Models\SalesReturnLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = 1;
        $unitPrice = 100;

        return [
            'company_id' => null,
            'sales_return_id' => SalesReturn::factory(),
            'warehouse_id' => Warehouse::factory(),
            'line_number' => 1,
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'sales_invoice_line_id' => null,
            'lot_id' => null,
            'serial_id' => null,
            'location_id' => null,
            'quantity' => $quantity,
            'base_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'unit_cost' => 60,
            'discount_percent' => 0,
            'discount_amount' => 0,
            'tax_id' => null,
            'tax_percent' => 0,
            'tax_amount' => 0,
            'withholding_percent' => 0,
            'withholding_amount' => 0,
            'subtotal' => $quantity * $unitPrice,
            'total' => $quantity * $unitPrice,
            'reason' => null,
            'condition' => null,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
