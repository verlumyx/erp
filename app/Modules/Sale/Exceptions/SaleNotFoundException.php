<?php

declare(strict_types=1);

namespace App\Modules\Sale\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SaleNotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Sale not found.')
    {
        parent::__construct($message);
    }
}
