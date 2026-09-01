<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\SalesCreditNote\Models\SalesCreditNoteLine>
 */
class SalesCreditNoteLineFactory extends Factory
{
    protected $model = \App\Modules\SalesCreditNote\Models\SalesCreditNoteLine::class;

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
            'sales_credit_note_id' => SalesCreditNote::factory(),
            'line_number' => 1,
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'sales_invoice_line_id' => null,
            'warehouse_id' => null,
            'lot_id' => null,
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
            'unit_cost' => 0,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
