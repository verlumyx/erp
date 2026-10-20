<?php

declare(strict_types=1);

namespace App\Modules\Pos\Exceptions;

use RuntimeException;

class PosRegisterBusyException extends RuntimeException
{
    protected $message = 'Esta caja registradora ya tiene un turno abierto por otro usuario.';
}
