<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Exceptions;

use Exception;

class ItemLotNotFoundException extends Exception
{
    protected $message = 'Item lot not found.';
}
