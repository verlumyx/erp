<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\Item\Models\Item;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Store\Models\StoreItem>
 */
class StoreItemFactory extends Factory
{
    protected $model = \App\Modules\Store\Models\StoreItem::class;

    private static int $sequence = 0;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sequence = ++self::$sequence;
        $title = ucfirst(fake()->unique()->words(3, true));

        return [
            'company_id' => Company::factory(),
            'code' => 'PUB'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT),
            'item_id' => Item::factory(),
            'slug' => Str::slug($title).'-'.$sequence,
            'title' => $title,
            'summary' => fake()->optional()->sentence(8),
            'description' => fake()->optional()->paragraph(),
            'is_featured' => 'no',
            'order' => 0,
            'published_at' => now(),
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_featured' => 'yes',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'inactive',
        ]);
    }
}
