<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Exceptions;

use Exception;

class ExchangeRateNotFoundException extends Exception
{
    protected $message = 'Exchange rate not found.';

    /**
     * Falta la tasa necesaria para valorar un documento. El mensaje es de cara
     * al usuario: dice qué moneda y qué fecha hay que cargar para desbloquear.
     */
    public static function forCurrency(string $currency, string $date): self
    {
        return new self(sprintf(
            'No hay tasa de cambio cargada para %s al %s.',
            $currency,
            $date,
        ));
    }
}
