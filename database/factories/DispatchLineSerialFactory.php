<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\Dispatch\Models\DispatchLineSerial;
use App\Modules\ItemSerial\Models\ItemSerial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DispatchLineSerial>
 */
class DispatchLineSerialFactory extends Factory
{
    protected $model = DispatchLineSerial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'dispatch_line_id' => DispatchLine::factory(),
            'dispatch_line_lot_id' => null,
            'line_number' => 1,
            'serial_id' => ItemSerial::factory(),
            'status' => 'active',
        ];
    }
}
