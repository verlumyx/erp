<?php

declare(strict_types=1);

namespace App\Modules\Transaction\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class InvalidTransactionException extends UnprocessableEntityHttpException
{
    public function __construct(string $message = 'Invalid transaction.')
    {
        parent::__construct($message);
    }
}
