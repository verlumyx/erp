<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Exceptions;

use Exception;

class SalesInvoiceNotFoundException extends Exception
{
    protected $message = 'Sales invoice not found.';
}
