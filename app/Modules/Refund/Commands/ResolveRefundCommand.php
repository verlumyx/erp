<?php

declare(strict_types=1);

namespace App\Modules\Refund\Commands;

class ResolveRefundCommand
{
    public function __construct(
        public readonly string $refundId,
        public readonly ?string $companyId,
        public readonly ?string $resolvedBy,
    ) {}
}
