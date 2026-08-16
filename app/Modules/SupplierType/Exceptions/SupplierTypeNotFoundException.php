<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Exceptions;

use Exception;

class SupplierTypeNotFoundException extends Exception
{
    protected $message = 'Supplier type not found.';
}
