<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Exceptions;

use Exception;

/** La cantidad del kardex siempre es positiva: el signo lo pone el tipo. */
class InvalidMovementQuantityException extends Exception
{
    protected $message = 'The movement quantity must be greater than zero.';
}
