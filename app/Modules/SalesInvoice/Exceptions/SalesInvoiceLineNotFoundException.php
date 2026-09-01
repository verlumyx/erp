<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Exceptions;

use Exception;

class SalesInvoiceLineNotFoundException extends Exception
{
    protected $message = 'Sales invoice line not found.';
}
