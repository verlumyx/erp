<?php

namespace Database\Factories;

use App\Modules\Import\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Import\Models\ImportCost>
 */
class ImportCostFactory extends Factory
{
    protected $model = \App\Modules\Import\Models\ImportCost::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_id' => Import::factory(),
            'line_number' => 1,
            'sourceable_type' => null,
            'sourceable_id' => null,
            'supplier_id' => null,
            'concept' => 'freight',
            'description' => null,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'amount' => 100,
            'converted_amount' => 100,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
