<?php

declare(strict_types=1);

namespace App\Modules\Refund\Commands;

use App\Modules\Refund\Requests\UpdateRefundRequest;

class UpdateRefundCommand
{
    public function __construct(
        public readonly float $amount,
        public readonly ?string $reason,
    ) {}

    public static function fromRequest(UpdateRefundRequest $request): self
    {
        return new self(
            amount: (float) $request->input('amount'),
            reason: $request->input('reason'),
        );
    }
}
