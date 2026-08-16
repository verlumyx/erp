<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Exceptions;

use Exception;

class PurchaseOrderNotFoundException extends Exception
{
    protected $message = 'Purchase order not found.';
}
