<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Exceptions;

use Exception;

class PriceListNotFoundException extends Exception
{
    protected $message = 'Price list not found.';
}
