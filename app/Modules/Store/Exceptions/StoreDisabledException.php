<?php

declare(strict_types=1);

namespace App\Modules\Store\Exceptions;

use Exception;

class StoreDisabledException extends Exception
{
    protected $message = 'La tienda no está disponible.';
}
