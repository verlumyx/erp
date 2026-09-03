<?php

declare(strict_types=1);

namespace App\Modules\Store\Exceptions;

use Exception;

class StoreItemNotFoundException extends Exception
{
    protected $message = 'Store item not found.';
}
