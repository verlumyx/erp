<?php

declare(strict_types=1);

namespace App\Modules\Refund\Exceptions;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RefundAlreadyResolvedException extends ConflictHttpException
{
    public function __construct(string $message = 'El reembolso ya fue resuelto y no puede modificarse.')
    {
        parent::__construct($message);
    }
}
