<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Exceptions;

use Exception;

/**
 * `location_id` no pertenece a `warehouse_id`. Se valida antes de escribir el
 * saldo: no existe existencia colgada de una ubicación de otra bodega.
 */
class InvalidStockLocationException extends Exception
{
    protected $message = 'La ubicación no pertenece a la bodega indicada.';
}
