<?php

namespace Database\Factories;

use App\Modules\Entry\Models\EntryLineLot;
use App\Modules\Import\Models\ImportLine;
use App\Modules\ItemLot\Models\ItemLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Import\Models\ImportLineLot>
 */
class ImportLineLotFactory extends Factory
{
    protected $model = \App\Modules\Import\Models\ImportLineLot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_line_id' => ImportLine::factory(),
            'line_number' => 1,
            'entry_line_lot_id' => EntryLineLot::factory(),
            'lot_id' => ItemLot::factory(),
            'base_quantity' => 10,
            'remaining_quantity' => 10,
            'allocation_base' => 250,
            'allocated_amount' => 0,
            'unit_delta' => 0,
            'new_unit_cost' => 25,
            'capitalized_amount' => 0,
            'variance_amount' => 0,
            'status' => 'active',
        ];
    }
}
