<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Exceptions;

use Exception;

class SupplierPaymentNotFoundException extends Exception
{
    protected $message = 'Supplier payment not found.';
}
