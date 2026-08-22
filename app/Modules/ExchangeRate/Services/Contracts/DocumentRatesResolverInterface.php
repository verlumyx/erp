<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Services\Contracts;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;

/**
 * Resuelve las tasas que congela un documento. Es la puerta que usan los
 * módulos de documentos: ninguno lee la configuración ni el catálogo de tasas
 * por su cuenta.
 */
interface DocumentRatesResolverInterface
{
    /**
     * Moneda del documento y moneda de la empresa, cada una con su tasa a la
     * fecha del documento.
     *
     * @param  string  $date  Fecha del documento en formato Y-m-d.
     * @param  string|null  $override  Tasa escrita a mano por el usuario. Solo
     *                                 cuenta si la empresa permite corregirla.
     *
     * @throws \App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException si falta alguna tasa.
     */
    public function forDocument(
        string $companyId,
        string $currency,
        string $date,
        ?string $override = null,
    ): DocumentRatesData;

    /**
     * Precio de una lista reexpresado en la moneda del documento.
     *
     * Cada lista de precio lleva su propia moneda, independiente de la del
     * documento: al capturar la línea el precio se convierte una vez y queda
     * congelado. La línea ya no recuerda de qué moneda venía.
     *
     * El resultado no viene redondeado: redondea quien lo persiste.
     *
     * @param  string  $date  Fecha del documento en formato Y-m-d.
     *
     * @throws \App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException si falta la tasa de la moneda del precio.
     */
    public function priceInDocumentCurrency(
        DocumentRatesData $rates,
        string $companyId,
        string $date,
        float $price,
        string $priceCurrency,
    ): float;
}
