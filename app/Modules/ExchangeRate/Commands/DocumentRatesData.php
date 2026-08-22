<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Commands;

/**
 * Las cuatro columnas de moneda que congela un documento.
 *
 * El primer par da el monto en bolívares; el segundo permite reexpresarlo en
 * la moneda de la empresa aunque esa moneda cambie después.
 */
class DocumentRatesData
{
    public function __construct(
        public readonly string $currency,
        public readonly float $exchangeRate,
        public readonly string $baseCurrency,
        public readonly float $baseExchangeRate,
        /**
         * Serie de tasas con la que se valoró el documento. No se persiste:
         * sirve para seguir resolviendo importes del mismo documento —los
         * precios de lista de sus líneas— contra la misma serie.
         */
        public readonly string $rateType = 'legal',
        /** Decimales con los que la empresa persiste un precio unitario. */
        public readonly int $priceDecimals = 6,
    ) {}

    /**
     * @return array{currency: string, exchange_rate: float, base_currency: string, base_exchange_rate: float}
     */
    public function toAttributes(): array
    {
        return [
            'currency' => $this->currency,
            'exchange_rate' => $this->exchangeRate,
            'base_currency' => $this->baseCurrency,
            'base_exchange_rate' => $this->baseExchangeRate,
        ];
    }
}
