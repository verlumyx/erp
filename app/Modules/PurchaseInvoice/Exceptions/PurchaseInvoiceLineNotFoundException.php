<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Exceptions;

use Exception;

class PurchaseInvoiceLineNotFoundException extends Exception
{
    protected $message = 'Purchase invoice line not found.';
}
