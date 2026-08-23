<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Exceptions;

use Exception;

class MovementAlreadyReversedException extends Exception
{
    protected $message = 'The movement was already reversed.';
}
