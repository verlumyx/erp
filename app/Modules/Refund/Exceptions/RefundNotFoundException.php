<?php

declare(strict_types=1);

namespace App\Modules\Refund\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RefundNotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Refund not found.')
    {
        parent::__construct($message);
    }
}
