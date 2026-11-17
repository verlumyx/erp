<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Exceptions;

use RuntimeException;

class LoadPlanNotFoundException extends RuntimeException
{
    protected $message = 'El plan de carga solicitado no existe o no pertenece a la empresa activa.';
}
