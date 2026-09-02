<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Commands;

use App\Modules\Client\Models\Client;
use App\Modules\Dispatch\Requests\CreateDispatchRequest;

class CreateDispatchCommand
{
    /**
     * @param  array<int, DispatchLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        /**
         * A quién va la mercancía: el alias del destinatario en el morph map y
         * su id. La pantalla solo crea despachos a un cliente; los de traslado
         * los arma `TransferMirrorDispatchService`.
         */
        public readonly string $recipientType,
        public readonly string $recipientId,
        public readonly string $warehouseId,
        public readonly string $dispatchDate,
        public readonly string $createdBy,
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
        public readonly float $freightAmount = 0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateDispatchRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            recipientType: Client::MORPH_ALIAS,
            recipientId: $request->string('client_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            dispatchDate: $request->string('dispatch_date')->toString(),
            createdBy: $request->user()->id,
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
            freightAmount: (float) $request->input('freight_amount', 0),
            notes: $request->input('notes'),
        );
    }
}
