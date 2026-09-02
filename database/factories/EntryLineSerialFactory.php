<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Entry\Models\EntryLine;
use App\Modules\Entry\Models\EntryLineSerial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntryLineSerial>
 */
class EntryLineSerialFactory extends Factory
{
    protected $model = EntryLineSerial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'entry_line_id' => EntryLine::factory(),
            'entry_line_lot_id' => null,
            'line_number' => 1,
            'serial_number' => 'S-'.$this->faker->unique()->numerify('#####'),
            /** La serie del maestro se resuelve al confirmar. */
            'serial_id' => null,
            'status' => 'active',
        ];
    }
}
