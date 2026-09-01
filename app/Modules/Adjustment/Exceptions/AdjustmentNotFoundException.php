<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Exceptions;

use Exception;

class AdjustmentNotFoundException extends Exception
{
    protected $message = 'Adjustment not found.';
}
