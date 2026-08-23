<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Exceptions;

use Exception;

class InvalidMovementTypeException extends Exception
{
    protected $message = 'The movement type is not part of the kardex.';
}
