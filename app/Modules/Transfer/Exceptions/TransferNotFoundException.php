<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Exceptions;

use Exception;

class TransferNotFoundException extends Exception
{
    protected $message = 'Transfer not found.';
}
