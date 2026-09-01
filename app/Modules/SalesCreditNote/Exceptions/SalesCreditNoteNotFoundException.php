<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Exceptions;

use Exception;

class SalesCreditNoteNotFoundException extends Exception
{
    protected $message = 'Sales credit note not found.';
}
