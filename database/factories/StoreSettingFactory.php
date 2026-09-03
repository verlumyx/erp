<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Store\Models\StoreSetting>
 */
class StoreSettingFactory extends Factory
{
    protected $model = \App\Modules\Store\Models\StoreSetting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'is_enabled' => 'no',
            'store_name' => fake()->company(),
            'logo_path' => null,
            'brand_color' => '#111827',
            'price_list_id' => null,
            'warehouse_id' => null,
            'shows_stock' => 'no',
            'allows_orders' => 'no',
            'default_client_type_id' => null,
            'shows_secondary_currency' => 'yes',
            'contact_phone' => null,
            'contact_email' => null,
            'store_url' => null,
            'api_key_hash' => null,
            'api_key_last_used_at' => null,
            'created_by' => User::factory(),
        ];
    }

    /** Tienda encendida y con llave conocida: la que usan los tests de la API. */
    public function enabledWithKey(string $plainKey = 'stk_test-key'): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => 'yes',
            'api_key_hash' => hash('sha256', $plainKey),
        ]);
    }

    public function allowingOrders(): static
    {
        return $this->state(fn (array $attributes): array => [
            'allows_orders' => 'yes',
        ]);
    }
}
