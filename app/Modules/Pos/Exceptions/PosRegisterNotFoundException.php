<?php

declare(strict_types=1);

namespace App\Modules\Pos\Exceptions;

use RuntimeException;

class PosRegisterNotFoundException extends RuntimeException
{
    protected $message = 'La caja registradora no existe o fue desactivada.';
}
