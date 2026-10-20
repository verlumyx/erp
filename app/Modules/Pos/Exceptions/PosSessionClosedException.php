<?php

declare(strict_types=1);

namespace App\Modules\Pos\Exceptions;

use RuntimeException;

class PosSessionClosedException extends RuntimeException
{
    protected $message = 'La sesión de caja está cerrada y no admite más transacciones.';
}
