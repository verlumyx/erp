<?php

declare(strict_types=1);

namespace App\Modules\Store\Exceptions;

use Exception;

class StoreOrderNotFoundException extends Exception
{
    protected $message = 'Pedido web no encontrado.';
}
