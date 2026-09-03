<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Adjustment\Models\AdjustmentLineLot;
use App\Modules\ItemLot\Models\ItemLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdjustmentLineLot>
 */
class AdjustmentLineLotFactory extends Factory
{
    protected $model = AdjustmentLineLot::class;

    /**
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
            'adjustment_line_id' => AdjustmentLine::factory(),
            'line_number' => 1,
            /** El ajuste corrige un lote que ya existe: nunca lo estrena. */
            'lot_id' => ItemLot::factory(),
            'counted_quantity' => $counted,
            'system_quantity' => $system,
            'difference_quantity' => $difference,
            'base_quantity' => $difference,
            'movement_type' => AdjustmentLine::MOVEMENT_IN,
            'unit_cost' => $unitCost,
            'total_cost' => abs($difference) * $unitCost,
            'status' => 'active',
            'notes' => null,
        ];
    }

    /** Un lote con faltante: se contó menos de lo que decía el sistema. */
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
