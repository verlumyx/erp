<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Exceptions;

use Exception;

/** Una contrapartida no se anula: anularla sería repetir el original. */
class MovementNotReversibleException extends Exception
{
    protected $message = 'A reversal movement cannot be reversed again.';
}
