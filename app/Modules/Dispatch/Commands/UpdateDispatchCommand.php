<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Commands;

use App\Modules\Client\Models\Client;
use App\Modules\Dispatch\Requests\UpdateDispatchRequest;

class UpdateDispatchCommand
{
    /**
     * @param  array<int, DispatchLineData>  $lines
     */
    public function __construct(
        /** A quién va la mercancía: alias del morph map e id del destinatario. */
        public readonly string $recipientType,
        public readonly string $recipientId,
        public readonly string $warehouseId,
        public readonly string $dispatchDate,
        public readonly array $lines = [],
        /** Documento origen. Un despacho directo, sin pedido previo, va vacío. */
        public readonly ?string $sourceableType = null,
        public readonly ?string $sourceableId = null,
        public readonly ?string $clientAddressId = null,
        /** Ruta y parada por las que sale, si la planificación ya las asignó. */
        public readonly ?string $routeId = null,
        public readonly ?string $routeStopId = null,
        public readonly ?string $driverId = null,
        public readonly ?string $vehiclePlate = null,
        public readonly ?string $carrier = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateDispatchRequest $request): self
    {
        return new self(
            recipientType: Client::MORPH_ALIAS,
            recipientId: $request->string('client_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            dispatchDate: $request->string('dispatch_date')->toString(),
            lines: DispatchLineData::collection($request->input('lines', [])),
            sourceableType: $request->input('sourceable_type'),
            sourceableId: $request->input('sourceable_id'),
            clientAddressId: $request->input('client_address_id'),
            routeId: $request->input('route_id'),
            routeStopId: $request->input('route_stop_id'),
            driverId: $request->input('driver_id'),
            vehiclePlate: $request->input('vehicle_plate'),
            carrier: $request->input('carrier'),
            trackingNumber: $request->input('tracking_number'),
            notes: $request->input('notes'),
        );
    }
}
