<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Exceptions;

use Exception;

class ItemSerialNotFoundException extends Exception
{
    protected $message = 'Item serial not found.';
}
