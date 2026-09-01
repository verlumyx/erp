<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Exceptions;

use Exception;

class SalesOrderLineNotFoundException extends Exception
{
    protected $message = 'Sales order line not found.';
}
