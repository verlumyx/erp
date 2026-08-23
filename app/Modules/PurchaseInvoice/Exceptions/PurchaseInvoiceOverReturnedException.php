<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Exceptions;

use Exception;

class PurchaseInvoiceOverReturnedException extends Exception
{
    protected $message = 'The returned quantity exceeds the invoiced quantity of the purchase invoice line.';
}
