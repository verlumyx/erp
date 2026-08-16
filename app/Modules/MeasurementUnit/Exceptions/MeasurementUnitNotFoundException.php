<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Exceptions;

use Exception;

class MeasurementUnitNotFoundException extends Exception
{
    protected $message = 'Measurement unit not found.';
}
