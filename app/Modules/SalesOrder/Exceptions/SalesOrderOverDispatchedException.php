<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Exceptions;

use Exception;

class SalesOrderOverDispatchedException extends Exception
{
    protected $message = 'The dispatched quantity cannot exceed the ordered one.';
}
