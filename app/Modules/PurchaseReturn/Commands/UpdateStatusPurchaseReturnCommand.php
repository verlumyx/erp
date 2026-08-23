<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Commands;

use App\Modules\PurchaseReturn\Requests\UpdateStatusPurchaseReturnRequest;

class UpdateStatusPurchaseReturnCommand
{
    public function __construct(
        public readonly string $status,
    ) {}

    public static function fromRequest(UpdateStatusPurchaseReturnRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
        );
    }
}
