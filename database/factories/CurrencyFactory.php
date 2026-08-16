<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Currency\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = \App\Modules\Currency\Models\Currency::class;

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
            'code' => 'C'.str_pad((string) $sequence, 2, '0', STR_PAD_LEFT),
            'name' => 'Moneda '.$sequence,
            'symbol' => '$',
            'order' => $sequence,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['status' => 'inactive']);
    }
}
