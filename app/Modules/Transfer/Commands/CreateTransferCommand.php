<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Commands;

use App\Modules\Transfer\Requests\CreateTransferRequest;

class CreateTransferCommand
{
    /**
     * @param  array<int, TransferLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $originWarehouseId,
        public readonly string $destinationWarehouseId,
        public readonly string $transferDate,
        public readonly string $createdBy,
        public readonly array $lines = [],
        /** Con bodega de tránsito el traslado va en dos pasos; sin ella, en uno. */
        public readonly ?string $transitWarehouseId = null,
        public readonly ?string $expectedDate = null,
        public readonly string $reason = 'restock',
        public readonly ?string $reasonDetail = null,
        public readonly ?string $driverId = null,
        public readonly ?string $vehiclePlate = null,
        /** Ruta por la que viaja, si la planificación ya la asignó. */
        public readonly ?string $routeId = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateTransferRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            originWarehouseId: $request->string('origin_warehouse_id')->toString(),
            destinationWarehouseId: $request->string('destination_warehouse_id')->toString(),
            transferDate: $request->string('transfer_date')->toString(),
            createdBy: $request->user()->id,
            lines: TransferLineData::collection($request->input('lines', [])),
            transitWarehouseId: $request->input('transit_warehouse_id'),
            expectedDate: $request->input('expected_date'),
            reason: (string) $request->input('reason', 'restock'),
            reasonDetail: $request->input('reason_detail'),
            driverId: $request->input('driver_id'),
            vehiclePlate: $request->input('vehicle_plate'),
            routeId: $request->input('route_id'),
            notes: $request->input('notes'),
        );
    }
}
