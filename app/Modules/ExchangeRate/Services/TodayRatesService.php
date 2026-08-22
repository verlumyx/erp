<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Services;

use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\Currency\Models\Currency;
use App\Modules\Currency\Repositories\Contracts\CurrencyRepositoryInterface;
use App\Modules\ExchangeRate\Services\Contracts\ExchangeRateResolverInterface;

/**
 * Tasas de hoy de la empresa activa, una por cada moneda del catálogo.
 *
 * Alimenta la prop compartida `todayRates`, que es lo que permite a una
 * pantalla mostrar el equivalente de un importe que todavía no existe —los
 * totales de un formulario mientras se captura— sin ir al servidor en cada
 * tecla. El importe de un documento ya guardado no la necesita: lleva su
 * propia tasa congelada.
 *
 * Nunca bloquea: una moneda sin tasa cargada no aparece en el mapa y la
 * pantalla se queda sin el equivalente. Bloquear es cosa de quien emite.
 */
class TodayRatesService
{
    public function __construct(
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly ExchangeRateResolverInterface $rates,
        private readonly ConfigurationFindService $configurations,
    ) {}

    /**
     * @return array<string, float>
     */
    public function execute(string $companyId): array
    {
        $type = $this->configurations->execute($companyId)->rate_type;
        $today = now()->toDateString();

        $resolved = [];

        /** @var Currency $currency */
        foreach ($this->currencies->active() as $currency) {
            $rate = $this->rates->tryRateFor($companyId, $currency->code, $today, $type);

            if ($rate !== null) {
                $resolved[$currency->code] = $rate;
            }
        }

        return $resolved;
    }
}
