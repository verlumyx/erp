<?php

declare(strict_types=1);

namespace App\Modules\Store\Exceptions;

use Exception;

class StoreCustomerNotFoundException extends Exception
{
    protected $message = 'Comprador no encontrado.';
}
