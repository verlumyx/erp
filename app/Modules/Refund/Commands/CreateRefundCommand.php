<?php

declare(strict_types=1);

namespace App\Modules\Refund\Commands;

use App\Modules\Refund\Requests\CreateRefundRequest;

class CreateRefundCommand
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $companyId,
        public readonly string $saleId,
        public readonly float $amount,
        public readonly ?string $reason,
        public readonly ?string $requestedBy,
    ) {}

    public static function fromRequest(CreateRefundRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            saleId: $request->string('sale_id')->toString(),
            amount: (float) $request->input('amount'),
            reason: $request->input('reason'),
            requestedBy: (string) $request->user()->id,
        );
    }
}
