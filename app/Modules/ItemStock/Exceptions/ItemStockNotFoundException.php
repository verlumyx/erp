<?php

declare(strict_types=1);

namespace App\Modules\ItemStock\Exceptions;

use Exception;

class ItemStockNotFoundException extends Exception
{
    protected $message = 'Item stock not found.';
}
