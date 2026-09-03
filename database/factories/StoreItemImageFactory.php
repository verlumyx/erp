<?php

namespace Database\Factories;

use App\Modules\Store\Models\StoreItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Store\Models\StoreItemImage>
 */
class StoreItemImageFactory extends Factory
{
    protected $model = \App\Modules\Store\Models\StoreItemImage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_item_id' => StoreItem::factory(),
            'company_id' => fn (array $attributes): ?string => StoreItem::find($attributes['store_item_id'])?->company_id,
            'path' => fn (array $attributes): string => sprintf(
                'store/%s/%s/%s.webp',
                StoreItem::find($attributes['store_item_id'])?->company_id,
                $attributes['store_item_id'],
                Str::uuid7(),
            ),
            'alt_text' => fake()->optional()->words(3, true),
            'order' => 0,
            'width' => 1600,
            'height' => 1200,
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
