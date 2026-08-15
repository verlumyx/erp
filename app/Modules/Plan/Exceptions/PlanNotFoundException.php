<?php

declare(strict_types=1);

namespace App\Modules\Plan\Exceptions;

use Exception;

class PlanNotFoundException extends Exception
{
    protected $message = 'Plan not found.';
}
