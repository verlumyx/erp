<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Exceptions;

use Exception;

class ClientAdvanceNotFoundException extends Exception
{
    protected $message = 'Client advance not found.';
}
