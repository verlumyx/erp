<?php

namespace Database\Factories;

use App\Modules\Entry\Models\EntryLine;
use App\Modules\Import\Models\Import;
use App\Modules\Item\Models\Item;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Import\Models\ImportLine>
 */
class ImportLineFactory extends Factory
{
    protected $model = \App\Modules\Import\Models\ImportLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_id' => Import::factory(),
            'line_number' => 1,
            'entry_line_id' => EntryLine::factory(),
            'item_id' => Item::factory(),
            'measurement_unit_id' => MeasurementUnit::factory(),
            'location_id' => null,
            'base_quantity' => 10,
            'remaining_quantity' => 10,
            'unit_cost' => 25,
            'base_value' => 250,
            'allocation_base' => 250,
            'allocated_amount' => 0,
            'unit_delta' => 0,
            'new_unit_cost' => 25,
            'capitalized_amount' => 0,
            'variance_amount' => 0,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
