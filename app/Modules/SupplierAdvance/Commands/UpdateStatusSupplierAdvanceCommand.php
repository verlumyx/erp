<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Commands;

use App\Modules\SupplierAdvance\Requests\UpdateStatusSupplierAdvanceRequest;

class UpdateStatusSupplierAdvanceCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusSupplierAdvanceRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
