<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Commands;

use App\Modules\Supplier\Requests\UpdateStatusSupplierRequest;

class UpdateStatusSupplierCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusSupplierRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
