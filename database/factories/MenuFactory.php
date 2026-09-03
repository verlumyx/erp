<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Menu\Models\Menu>
 */
class MenuFactory extends Factory
{
    protected $model = \App\Modules\Menu\Models\Menu::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'parent_id' => null,
            'title' => ucfirst(str_replace('-', ' ', $slug)),
            'url' => '/'.$slug,
            'permission' => $slug.'.list',
            'icon' => 'Folder',
            'is_active' => true,
            'order' => fake()->numberBetween(1, 50),
            'section' => 'main',
        ];
    }

    /** Un grupo: sin URL ni permiso propio, se ve solo si algún hijo se ve. */
    public function group(): static
    {
        return $this->state(fn (array $attributes): array => [
            'url' => null,
            'permission' => null,
        ]);
    }
}
