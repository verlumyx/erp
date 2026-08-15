<?php

declare(strict_types=1);

namespace App\Modules\Account\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AccountNotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Account not found.')
    {
        parent::__construct($message);
    }
}
