<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Commands;

use App\Modules\SupplierPayment\Requests\UpdateStatusSupplierPaymentRequest;

class UpdateStatusSupplierPaymentCommand
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $cancellationReason = null,
    ) {}

    public static function fromRequest(UpdateStatusSupplierPaymentRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            cancellationReason: $request->input('cancellation_reason'),
        );
    }
}
