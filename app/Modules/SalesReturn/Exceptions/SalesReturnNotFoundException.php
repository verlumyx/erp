<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Exceptions;

use Exception;

class SalesReturnNotFoundException extends Exception
{
    protected $message = 'Sales return not found.';
}
