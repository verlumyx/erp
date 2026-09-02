<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Commands;

use App\Modules\Transfer\Requests\UpdateTransferRequest;

class UpdateTransferCommand
{
    /**
     * @param  array<int, TransferLineData>  $lines
     */
    public function __construct(
        public readonly string $originWarehouseId,
        public readonly string $destinationWarehouseId,
        public readonly string $transferDate,
        public readonly array $lines = [],
        public readonly ?string $expectedDate = null,
        public readonly string $reason = 'restock',
        public readonly ?string $reasonDetail = null,
        public readonly ?string $driverId = null,
        public readonly ?string $vehiclePlate = null,
        /** Ruta por la que viaja, si la planificación ya la asignó. */
        public readonly ?string $routeId = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateTransferRequest $request): self
    {
        return new self(
            originWarehouseId: $request->string('origin_warehouse_id')->toString(),
            destinationWarehouseId: $request->string('destination_warehouse_id')->toString(),
            transferDate: $request->string('transfer_date')->toString(),
            lines: TransferLineData::collection($request->input('lines', [])),
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
