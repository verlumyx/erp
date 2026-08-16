<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Exceptions;

use Exception;

class SupplierNotFoundException extends Exception
{
    protected $message = 'Supplier not found.';
}
