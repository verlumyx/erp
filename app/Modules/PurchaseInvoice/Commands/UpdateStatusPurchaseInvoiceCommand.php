<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Commands;

use App\Modules\PurchaseInvoice\Requests\UpdateStatusPurchaseInvoiceRequest;

class UpdateStatusPurchaseInvoiceCommand
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $cancellationReason = null,
    ) {}

    public static function fromRequest(UpdateStatusPurchaseInvoiceRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            cancellationReason: $request->input('cancellation_reason'),
        );
    }
}
