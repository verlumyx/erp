<?php

namespace Database\Seeders;

use App\Modules\Currency\Models\Currency;
use Illuminate\Database\Seeder;

/**
 * Monedas del sistema. Es un catálogo global (sin `company_id`): la misma
 * lista alimenta los selects de moneda de todos los módulos.
 */
class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'Dólar estadounidense', 'symbol' => '$', 'order' => 1],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'order' => 2],
            ['code' => 'VES', 'name' => 'Bolívares', 'symbol' => 'Bs.', 'order' => 3],
        ];

        foreach ($currencies as $currency) {
            Currency::query()->updateOrCreate(
                ['code' => $currency['code']],
                [...$currency, 'status' => 'active'],
            );
        }
    }
}
