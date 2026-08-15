<?php

declare(strict_types=1);

namespace App\Modules\ManualTransaction\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ManualTransactionNotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Manual transaction not found.')
    {
        parent::__construct($message);
    }
}
