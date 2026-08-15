<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class InvalidManualTransactionStatusException extends ConflictHttpException
{
    public function __construct(string $message = 'La transacción manual ya fue resuelta y no puede modificarse.')
    {
        parent::__construct($message);
    }
}
