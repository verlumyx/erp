<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Exceptions;

use Exception;

class PurchaseCreditNoteNotFoundException extends Exception
{
    protected $message = 'Purchase credit note not found.';
}
