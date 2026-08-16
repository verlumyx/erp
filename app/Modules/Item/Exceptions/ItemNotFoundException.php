<?php

declare(strict_types=1);

namespace App\Modules\Item\Exceptions;

use Exception;

class ItemNotFoundException extends Exception
{
    protected $message = 'Item not found.';
}
