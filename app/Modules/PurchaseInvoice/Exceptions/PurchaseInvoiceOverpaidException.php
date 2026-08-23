<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Exceptions;

use Exception;

class PurchaseInvoiceOverpaidException extends Exception
{
    protected $message = 'The applied amount exceeds the outstanding balance of the purchase invoice.';
}
