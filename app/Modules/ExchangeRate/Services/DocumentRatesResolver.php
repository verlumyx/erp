<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Services;

use App\Modules\Configuration\Models\Configuration;
use App\Modules\Configuration\Services\ConfigurationFindService;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\ExchangeRate\Services\Contracts\ExchangeRateResolverInterface;

class DocumentRatesResolver implements DocumentRatesResolverInterface
{
    public function __construct(
        private readonly ExchangeRateResolverInterface $rates,
        private readonly ConfigurationFindService $configurations,
    ) {}

    public function forDocument(
        string $companyId,
        string $currency,
        string $date,
        ?string $override = null,
    ): DocumentRatesData {
        $configuration = $this->configurations->execute($companyId);

        $currency = strtoupper($currency);
        $baseCurrency = strtoupper($configuration->base_currency);
        $type = $configuration->rate_type;

        $exchangeRate = $this->manualRate($configuration, $override)
            ?? $this->rates->rateFor($companyId, $currency, $date, $type);

        return new DocumentRatesData(
            currency: $currency,
            exchangeRate: $exchangeRate,
            baseCurrency: $baseCurrency,
            /**
             * Una orden emitida en la moneda de la empresa lleva la misma tasa
             * en los dos pares: volver a pedirla devolvería otro número si el
             * usuario acaba de corregirla a mano.
             */
            baseExchangeRate: $baseCurrency === $currency
                ? $exchangeRate
                : $this->rates->rateFor($companyId, $baseCurrency, $date, $type),
            rateType: $type,
            priceDecimals: $configuration->price_decimals,
        );
    }

    public function priceInDocumentCurrency(
        DocumentRatesData $rates,
        string $companyId,
        string $date,
        float $price,
        string $priceCurrency,
    ): float {
        $priceCurrency = strtoupper($priceCurrency);

        if ($priceCurrency === $rates->currency) {
            return $price;
        }

        /**
         * Se cruza por bolívares contra la tasa que ya lleva el documento, no
         * contra la del catálogo: si el usuario corrigió la tasa a mano, el
         * precio convertido tiene que respetar esa corrección.
         */
        $inLocal = $price * $this->rates->rateFor($companyId, $priceCurrency, $date, $rates->rateType);

        return $inLocal / $rates->exchangeRate;
    }

    /**
     * La tasa que escribe el usuario solo cuenta si la empresa lo permite.
     * Cualquier otro caso cae en la del catálogo.
     */
    private function manualRate(Configuration $configuration, ?string $override): ?float
    {
        if ($override === null || $configuration->allows_rate_override !== 'yes') {
            return null;
        }

        $rate = (float) $override;

        return $rate > 0 ? $rate : null;
    }
}
