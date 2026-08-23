<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Exceptions;

use Exception;

/** Servicios y artículos no inventariados no llegan al kardex. */
class NonInventoriedItemException extends Exception
{
    protected $message = 'The item does not affect stock, so it cannot move the kardex.';
}
