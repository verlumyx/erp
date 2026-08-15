<?php

declare(strict_types=1);

namespace App\Modules\Sale\Commands;

use App\Modules\Sale\Requests\CancelSaleRequest;

class CancelSaleCommand
{
    public function __construct(
        public readonly string $saleId,
        public readonly ?string $companyId,
        public readonly string $cancellationReason,
        public readonly bool $createRefund = false,
        public readonly ?float $refundAmount = null,
        public readonly ?string $refundReason = null,
        public readonly ?string $requestedBy = null,
    ) {}

    public static function fromRequest(
        CancelSaleRequest $request,
        string $saleId,
        ?string $companyId = null,
    ): self {
        $createRefund = $request->boolean('create_refund');

        return new self(
            saleId: $saleId,
            companyId: $companyId,
            cancellationReason: $request->string('cancellation_reason')->toString(),
            createRefund: $createRefund,
            refundAmount: $createRefund && $request->filled('refund_amount')
                ? (float) $request->input('refund_amount')
                : null,
            refundReason: $createRefund ? $request->input('refund_reason') : null,
            requestedBy: (string) $request->user()->id,
        );
    }
}
