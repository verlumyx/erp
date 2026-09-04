<?php

namespace Database\Factories;

use App\Modules\Entry\Models\Entry;
use App\Modules\Import\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Import\Models\ImportEntry>
 */
class ImportEntryFactory extends Factory
{
    protected $model = \App\Modules\Import\Models\ImportEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'import_id' => Import::factory(),
            'entry_id' => Entry::factory(),
            'line_number' => 1,
            'status' => 'active',
        ];
    }
}
