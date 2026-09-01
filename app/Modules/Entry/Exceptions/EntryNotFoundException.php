<?php

declare(strict_types=1);

namespace App\Modules\Entry\Exceptions;

use Exception;

class EntryNotFoundException extends Exception
{
    protected $message = 'Entry not found.';
}
