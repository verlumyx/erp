<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Exceptions;

use Exception;

class SalesInvoiceOverReturnedException extends Exception
{
    protected $message = 'The sales invoice line cannot be returned beyond its invoiced quantity.';
}
