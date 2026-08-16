<?php

declare(strict_types=1);

namespace App\Modules\Tax\Exceptions;

use Exception;

class TaxNotFoundException extends Exception
{
    protected $message = 'Tax not found.';
}
