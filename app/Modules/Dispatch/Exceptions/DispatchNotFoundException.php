<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Exceptions;

use Exception;

class DispatchNotFoundException extends Exception
{
    protected $message = 'Dispatch not found.';
}
