<?php

declare(strict_types=1);

namespace App\Modules\Vehicle\Exceptions;

use RuntimeException;

class VehicleNotFoundException extends RuntimeException
{
    protected $message = 'El camión / vehículo solicitado no existe o no pertenece a la empresa activa.';
}
