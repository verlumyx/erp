<?php

namespace Database\Factories;

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Adjustment\Models\AdjustmentLine>
 */
class AdjustmentLineFactory extends Factory
{
    protected $model = AdjustmentLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $system = 10;
        $counted = 12;
        $difference = $counted - $system;
        $unitCost = 25;

        return [
            'company_id' => null,
            'adjustment_id' => Adjustment::factory(),
            'line_number' => 1,
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'location_id' => null,
            'lot_id' => null,
            'serial_id' => null,
            'system_quantity' => $system,
            'counted_quantity' => $counted,
            'difference_quantity' => $difference,
            'base_quantity' => $difference,
            'movement_type' => AdjustmentLine::MOVEMENT_IN,
            'unit_cost' => $unitCost,
            'total_cost' => abs($difference) * $unitCost,
            'reason' => null,
            'counted_by' => null,
            'status' => 'active',
            'notes' => null,
        ];
    }

    /** Una línea con faltante: se contó menos de lo que decía el sistema. */
    public function shortage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'counted_quantity' => 7,
            'difference_quantity' => -3,
            'base_quantity' => -3,
            'movement_type' => AdjustmentLine::MOVEMENT_OUT,
            'total_cost' => 3 * (float) $attributes['unit_cost'],
        ]);
    }
}
