<?php

declare(strict_types=1);

namespace App\Modules\Pos\Exceptions;

use RuntimeException;

class PosSaleNotFoundException extends RuntimeException
{
    protected $message = 'La venta POS no existe.';
}
