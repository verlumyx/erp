<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Commands;

use App\Modules\Warehouse\Requests\UpdateStatusWarehouseRequest;

class UpdateStatusWarehouseCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusWarehouseRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
