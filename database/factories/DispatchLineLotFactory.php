<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\Dispatch\Models\DispatchLineLot;
use App\Modules\ItemLot\Models\ItemLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DispatchLineLot>
 */
class DispatchLineLotFactory extends Factory
{
    protected $model = DispatchLineLot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = 1;

        return [
            'company_id' => null,
            'dispatch_line_id' => DispatchLine::factory(),
            'line_number' => 1,
            /** El despacho consume un lote que ya existe: nunca lo estrena. */
            'lot_id' => ItemLot::factory(),
            'quantity' => $quantity,
            'base_quantity' => $quantity,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
