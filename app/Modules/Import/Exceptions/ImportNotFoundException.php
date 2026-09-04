<?php

declare(strict_types=1);

namespace App\Modules\Import\Exceptions;

use Exception;

class ImportNotFoundException extends Exception
{
    protected $message = 'Import not found.';
}
