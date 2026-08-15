<?php

declare(strict_types=1);

namespace App\Modules\Client\Exceptions;

use Exception;

class ClientNotFoundException extends Exception
{
    protected $message = 'Client not found.';
}
