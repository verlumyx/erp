<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Exceptions;

use Exception;

class ItemSerialLotMismatchException extends Exception
{
    protected $message = 'El lote debe pertenecer al mismo artículo que la serie.';
}
