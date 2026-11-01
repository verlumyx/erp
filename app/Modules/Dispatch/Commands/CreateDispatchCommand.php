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
        public readonly ?string $recipientType = null,
        public readonly ?string $recipientId = null,
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
        public readonly ?string $notes = null,
        public readonly ?string $controlNumber = null,
        public readonly bool $isSale = true,
        public readonly ?string $transferReason = null,
        public readonly ?string $transferReasonDetail = null,
    ) {}

    public static function fromRequest(CreateDispatchRequest $request, ?string $companyId = null): self
    {
        $isTransfer = $request->string('sourceable_type')->toString() === 'transfer';
        $recipientType = $isTransfer
            ? null
            : ($request->string('recipient_type')->toString() ?: ($request->string('client_id')->toString() ? Client::MORPH_ALIAS : null));
        $recipientId = $isTransfer
            ? null
            : ($request->string('recipient_id')->toString() ?: $request->string('client_id')->toString() ?: null);

        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            recipientType: $recipientType,
            recipientId: $recipientId,
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
            notes: $request->input('notes'),
            controlNumber: $request->input('control_number'),
            isSale: $request->has('is_sale') ? $request->boolean('is_sale') : true,
            transferReason: $request->input('transfer_reason'),
            transferReasonDetail: $request->input('transfer_reason_detail'),
        );
    }
}
