<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Exceptions;

use Exception;

class PurchaseInvoiceNotFoundException extends Exception
{
    protected $message = 'Purchase invoice not found.';
}
