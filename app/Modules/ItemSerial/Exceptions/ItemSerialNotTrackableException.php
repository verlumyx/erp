<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Exceptions;

use Exception;

/**
 * El artículo no existe, es de otra empresa o no es de tipo `serialized`: solo
 * el artículo serializado se controla unidad por unidad.
 */
class ItemSerialNotTrackableException extends Exception
{
    protected $message = 'El artículo no existe o no se controla por número de serie.';
}
