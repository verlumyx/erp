<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Exceptions;

use Exception;

class InvalidPurchaseOrderReceiptException extends Exception
{
    protected $message = 'The received quantity of a purchase order line cannot go below zero.';
}
