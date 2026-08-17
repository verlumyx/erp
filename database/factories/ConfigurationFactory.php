<?php

namespace Database\Factories;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Configuration\Models\Configuration>
 */
class ConfigurationFactory extends Factory
{
    protected $model = \App\Modules\Configuration\Models\Configuration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'base_currency' => 'USD',
            'secondary_currency' => 'VES',
            'rate_type' => 'legal',
            'allows_rate_override' => 'yes',
            'amount_decimals' => 2,
            'price_decimals' => 6,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Empresa que lleva sus cifras en bolívares: no hay conversión ni doble
     * monto en ninguna pantalla.
     */
    public function inBolivares(): static
    {
        return $this->state(fn (array $attributes): array => [
            'base_currency' => 'VES',
            'secondary_currency' => 'VES',
        ]);
    }
}
