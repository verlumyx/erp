<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Commands;

use App\Modules\SalesReturn\Requests\UpdateStatusSalesReturnRequest;

class UpdateStatusSalesReturnCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusSalesReturnRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
