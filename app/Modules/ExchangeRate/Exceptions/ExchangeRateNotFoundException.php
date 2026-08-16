<?php

declare(strict_types=1);

namespace App\Modules\ExchangeRate\Exceptions;

use Exception;

class ExchangeRateNotFoundException extends Exception
{
    protected $message = 'Exchange rate not found.';
}
