<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Exceptions;

use Exception;

class WarehouseNotFoundException extends Exception
{
    protected $message = 'Warehouse not found.';
}
