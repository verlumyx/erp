<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\Entry\Models\EntryLine;
use App\Modules\Entry\Models\EntryLineLot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EntryLineLot>
 */
class EntryLineLotFactory extends Factory
{
    protected $model = EntryLineLot::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = 10;

        return [
            'company_id' => null,
            'entry_line_id' => EntryLine::factory(),
            'line_number' => 1,
            'lot_number' => 'L-'.$this->faker->unique()->numerify('#####'),
            /** El lote del maestro se resuelve al confirmar, no al sembrar. */
            'lot_id' => null,
            'expires_at' => null,
            'quantity' => $quantity,
            'base_quantity' => $quantity,
            'status' => 'active',
            'notes' => null,
        ];
    }
}
