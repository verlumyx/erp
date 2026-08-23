<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Exceptions;

use Exception;

class SupplierAdvanceNotFoundException extends Exception
{
    protected $message = 'Supplier advance not found.';
}
