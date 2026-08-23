<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Exceptions;

use Exception;

class PurchaseReturnAlreadyCreditedException extends Exception
{
    protected $message = 'The purchase return already has a credit note.';
}
