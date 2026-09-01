<?php

declare(strict_types=1);

namespace App\Modules\Route\Commands;

use App\Modules\Route\Requests\RegisterRouteStopVisitRequest;

/**
 * Lo que ocurrió en una parada: a qué hora se llegó, cómo terminó la visita y
 * dónde estaba el vehículo al registrarla.
 */
class RegisterRouteStopVisitCommand
{
    public function __construct(
        public readonly string $stopStatus,
        public readonly ?string $actualArrival = null,
        public readonly ?string $actualDeparture = null,
        public readonly ?string $skipReason = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
    ) {}

    public static function fromRequest(RegisterRouteStopVisitRequest $request): self
    {
        return new self(
            stopStatus: $request->string('stop_status')->toString(),
            actualArrival: $request->input('actual_arrival'),
            actualDeparture: $request->input('actual_departure'),
            skipReason: $request->input('skip_reason'),
            latitude: $request->filled('latitude') ? (float) $request->input('latitude') : null,
            longitude: $request->filled('longitude') ? (float) $request->input('longitude') : null,
        );
    }
}
