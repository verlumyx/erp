<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\ItemSerial\Models\ItemSerial>
 */
class ItemSerialFactory extends Factory
{
    protected $model = \App\Modules\ItemSerial\Models\ItemSerial::class;

    private static int $sequence = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = ++self::$sequence;

        return [
            'item_id' => Item::factory()->state(['type' => 'serialized']),
            'company_id' => fn (array $attributes): ?string => Item::find($attributes['item_id'])?->company_id,
            'code' => 'SER'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-'.str_pad((string) $sequence, 8, '0', STR_PAD_LEFT),
            'lot_id' => null,
            'warehouse_id' => null,
            'status' => 'available',
            'sold_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the unit is committed to a sales document.
     */
    public function reserved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'reserved',
        ]);
    }

    /**
     * Indicate that the unit has definitively left the warehouse.
     */
    public function sold(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'sold',
            'sold_at' => now(),
        ]);
    }
}
