<?php

declare(strict_types=1);

namespace App\Modules\Pos\Exceptions;

use RuntimeException;

class PosSessionNotFoundException extends RuntimeException
{
    protected $message = 'La sesión de caja no existe.';
}
