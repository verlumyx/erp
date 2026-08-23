<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Exceptions;

use Exception;

/**
 * El artículo no admite control por lote: no existe, es de otra empresa o su
 * tipo no afecta inventario (un servicio, por ejemplo).
 */
class ItemLotNotTrackableException extends Exception
{
    protected $message = 'El artículo no existe o no admite control por lote.';
}
