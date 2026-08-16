<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Exceptions;

use Exception;

class WarehouseLocationNotFoundException extends Exception
{
    protected $message = 'Warehouse location not found.';
}
