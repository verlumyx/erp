<?php

declare(strict_types=1);

namespace App\Modules\Sale\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Se lanza al reactivar una venta cuyos profiles originales ya están ocupados por
 * otra venta y el agente no proporcionó profiles de reemplazo. El endpoint responde
 * 409 con la lista de profiles no disponibles para que el agente elija otros.
 */
class ProfilesUnavailableException extends RuntimeException
{
    /**
     * @param  array<int, array{id: string, label: string}>  $unavailableProfiles
     */
    public function __construct(
        public readonly array $unavailableProfiles,
        string $message = 'Algunos profiles ya no están disponibles. Selecciona otros profiles del mismo servicio.',
    ) {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return new JsonResponse([
            'message' => $this->getMessage(),
            'unavailable_profiles' => $this->unavailableProfiles,
        ], 409);
    }
}
