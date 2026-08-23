<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Exceptions;

use Exception;

class PurchaseReturnNotFoundException extends Exception
{
    protected $message = 'Purchase return not found.';
}
