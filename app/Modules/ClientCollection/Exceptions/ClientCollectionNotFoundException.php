<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Exceptions;

use Exception;

class ClientCollectionNotFoundException extends Exception
{
    protected $message = 'Client collection not found.';
}
