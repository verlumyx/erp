<?php

declare(strict_types=1);

namespace App\Modules\Route\Exceptions;

use Exception;

class RouteStopNotFoundException extends Exception
{
    protected $message = 'Route stop not found.';
}
