<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Exceptions;

use Exception;

class PurchaseOrderLineNotFoundException extends Exception
{
    protected $message = 'Purchase order line not found.';
}
