<?php

declare(strict_types=1);

namespace App\Modules\Service\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceNotFoundException extends NotFoundHttpException
{
    public function __construct(string $message = 'Service not found.')
    {
        parent::__construct($message);
    }
}
