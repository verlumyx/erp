<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Services\Contracts;

/**
 * Punto único de conversión de moneda del sistema. Ningún módulo consulta la
 * tabla de tasas por su cuenta ni multiplica montos a mano.
 */
interface ExchangeRateResolverInterface
{
    /**
     * Bolívares que vale 1 unidad de `$currency` a la fecha dada.
     *
     * Devuelve 1 para el propio bolívar. Si no hay tasa exacta del día usa la
     * última anterior (el emisor legal no publica fines de semana ni feriados).
     *
     * @param  string  $date  Fecha del documento en formato Y-m-d.
     *
     * @throws \App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException si no hay ninguna tasa aplicable.
     */
    public function rateFor(string $companyId, string $currency, string $date, string $type = 'legal'): float;

    /**
     * La misma tasa que `rateFor()`, pero devuelve `null` en vez de bloquear.
     *
     * Es para quien muestra: una pantalla que enseña el equivalente de un
     * importe se queda sin él, no se cae. Quien emite un documento usa
     * `rateFor()`, que sí bloquea.
     *
     * @param  string  $date  Fecha del documento en formato Y-m-d.
     */
    public function tryRateFor(string $companyId, string $currency, string $date, string $type = 'legal'): ?float;

    /**
     * Convierte un monto entre dos monedas cualesquiera cruzando por bolívares.
     *
     * El resultado no viene redondeado: redondea quien lo persiste, según los
     * decimales del documento.
     *
     * @param  string  $date  Fecha del documento en formato Y-m-d.
     *
     * @throws \App\Modules\ExchangeRate\Exceptions\ExchangeRateNotFoundException si falta alguna de las dos tasas.
     */
    public function convert(float $amount, string $from, string $to, string $companyId, string $date, string $type = 'legal'): float;
}
