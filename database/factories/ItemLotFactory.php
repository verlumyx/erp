<?php

namespace Database\Factories;

use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\ItemLot\Models\ItemLot>
 */
class ItemLotFactory extends Factory
{
    protected $model = \App\Modules\ItemLot\Models\ItemLot::class;

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
            'item_id' => Item::factory(),
            'company_id' => fn (array $attributes): ?string => Item::find($attributes['item_id'])?->company_id,
            'code' => 'LOT'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'lot_number' => 'L-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'manufactured_at' => null,
            'expires_at' => null,
            'supplier_id' => null,
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate that the lot expires on the given date.
     */
    public function expiring(string $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => $date,
        ]);
    }

    /**
     * Indicate that the lot is held and cannot be dispatched.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'blocked',
        ]);
    }

    /**
     * Indicate that the lot is past its expiration date.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'expired',
        ]);
    }
}
