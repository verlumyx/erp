<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Commands;

use App\Modules\ItemSerial\Requests\UpdateItemSerialRequest;

class UpdateItemSerialCommand
{
    public function __construct(
        public readonly string $serialNumber,
        public readonly ?string $lotId = null,
        public readonly ?string $warehouseId = null,
    ) {}

    public static function fromRequest(UpdateItemSerialRequest $request): self
    {
        return new self(
            serialNumber: $request->string('serial_number')->toString(),
            lotId: $request->input('lot_id'),
            warehouseId: $request->input('warehouse_id'),
        );
    }
}
