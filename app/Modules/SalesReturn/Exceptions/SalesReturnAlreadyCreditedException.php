<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Exceptions;

use Exception;

class SalesReturnAlreadyCreditedException extends Exception
{
    protected $message = 'The sales return already has a credit note.';
}
