<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Exceptions;

use Exception;

class SalesInvoiceOvercollectedException extends Exception
{
    protected $message = 'The applied amount does not fit in the sales invoice.';
}
