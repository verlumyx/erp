<?php

declare(strict_types=1);

namespace App\Modules\Pos\Exceptions;

use RuntimeException;

class PosUnauthorizedSessionException extends RuntimeException
{
    protected $message = 'Esta sesión de caja pertenece a otro usuario y no puedes operar en ella.';
}
