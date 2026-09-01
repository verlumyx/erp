<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Commands;

use App\Modules\Dispatch\Requests\RegisterDispatchDeliveryRequest;

/**
 * El resultado del viaje: qué recibió el cliente, quién firmó y dónde.
 *
 * Es un comando aparte del cambio de estado porque no mueve el documento por su
 * ciclo de vida: lo que registra es el hecho físico de la entrega, y de él sale
 * el reingreso de lo que no se quedó el cliente.
 */
class RegisterDispatchDeliveryCommand
{
    /**
     * @param  array<int, DispatchDeliveryLineData>  $lines
     */
    public function __construct(
        public readonly string $deliveryStatus,
        public readonly array $lines = [],
        public readonly ?string $deliveryDate = null,
        public readonly ?string $receivedByName = null,
        public readonly ?string $receivedByDocument = null,
        public readonly ?string $signaturePath = null,
        public readonly ?string $evidencePath = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $rejectionReason = null,
    ) {}

    public static function fromRequest(RegisterDispatchDeliveryRequest $request): self
    {
        return new self(
            deliveryStatus: $request->string('delivery_status')->toString(),
            lines: DispatchDeliveryLineData::collection($request->input('lines', [])),
            deliveryDate: $request->input('delivery_date'),
            receivedByName: $request->input('received_by_name'),
            receivedByDocument: $request->input('received_by_document'),
            signaturePath: $request->input('signature_path'),
            evidencePath: $request->input('evidence_path'),
            latitude: $request->filled('latitude') ? (float) $request->input('latitude') : null,
            longitude: $request->filled('longitude') ? (float) $request->input('longitude') : null,
            rejectionReason: $request->input('rejection_reason'),
        );
    }
}
