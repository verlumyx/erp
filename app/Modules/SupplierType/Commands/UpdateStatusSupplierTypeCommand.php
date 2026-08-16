<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Commands;

use App\Modules\SupplierType\Requests\UpdateStatusSupplierTypeRequest;

class UpdateStatusSupplierTypeCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusSupplierTypeRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
