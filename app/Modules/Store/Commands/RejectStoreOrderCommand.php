<?php

declare(strict_types=1);

namespace App\Modules\Store\Commands;

use App\Modules\Store\Requests\RejectStoreOrderRequest;

class RejectStoreOrderCommand
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $companyId,
        public readonly string $rejectionReason,
    ) {}

    public static function fromRequest(RejectStoreOrderRequest $request, string $company, string $id): self
    {
        return new self(
            orderId: $id,
            companyId: $company,
            rejectionReason: $request->string('rejection_reason')->toString(),
        );
    }
}
