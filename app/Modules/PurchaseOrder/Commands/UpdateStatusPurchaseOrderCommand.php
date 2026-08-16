<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Commands;

use App\Modules\PurchaseOrder\Requests\UpdateStatusPurchaseOrderRequest;

class UpdateStatusPurchaseOrderCommand
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $cancellationReason = null,
        public readonly ?string $approvedBy = null,
    ) {}

    public static function fromRequest(UpdateStatusPurchaseOrderRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            cancellationReason: $request->input('cancellation_reason'),
            approvedBy: $request->user()?->id,
        );
    }
}
