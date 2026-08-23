<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Exceptions;

use Exception;

class InventoryMovementNotFoundException extends Exception
{
    protected $message = 'Inventory movement not found.';
}
