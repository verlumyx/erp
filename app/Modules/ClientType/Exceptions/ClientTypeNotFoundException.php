<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Exceptions;

use Exception;

class ClientTypeNotFoundException extends Exception
{
    protected $message = 'Client type not found.';
}
