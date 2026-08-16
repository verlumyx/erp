<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Commands;

use App\Modules\WarehouseLocation\Requests\UpdateStatusWarehouseLocationRequest;

class UpdateStatusWarehouseLocationCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusWarehouseLocationRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
