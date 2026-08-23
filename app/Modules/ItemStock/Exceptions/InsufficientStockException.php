<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Exceptions;

use Exception;

/**
 * La salida dejaría el saldo por debajo de cero en una bodega que no admite
 * existencia negativa (`allows_negative_stock = 'no'`).
 */
class InsufficientStockException extends Exception
{
    protected $message = 'La bodega no admite existencia negativa: el saldo disponible es insuficiente.';
}
