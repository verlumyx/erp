<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Adjustment\Models\AdjustmentLineSerial;
use App\Modules\ItemSerial\Models\ItemSerial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdjustmentLineSerial>
 */
class AdjustmentLineSerialFactory extends Factory
{
    protected $model = AdjustmentLineSerial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'adjustment_line_id' => AdjustmentLine::factory(),
            'adjustment_line_lot_id' => null,
            'line_number' => 1,
            'serial_id' => ItemSerial::factory(),
            'status' => 'active',
        ];
    }
}
