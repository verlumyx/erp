<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Exceptions;

use Exception;

class SalesOrderNotFoundException extends Exception
{
    protected $message = 'Sales order not found.';
}
