<?php

declare(strict_types=1);

namespace App\Modules\Pos\Exceptions;

use RuntimeException;

class PosSessionAlreadyOpenException extends RuntimeException
{
    protected $message = 'Ya tienes una sesión de caja abierta en esta empresa.';
}
