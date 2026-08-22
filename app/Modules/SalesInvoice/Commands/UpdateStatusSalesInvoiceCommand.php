<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Commands;

use App\Modules\SalesInvoice\Requests\UpdateStatusSalesInvoiceRequest;

class UpdateStatusSalesInvoiceCommand
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $cancellationReason = null,
    ) {}

    public static function fromRequest(UpdateStatusSalesInvoiceRequest $request): self
    {
        return new self(
            status: $request->string('status')->toString(),
            cancellationReason: $request->input('cancellation_reason'),
        );
    }
}
