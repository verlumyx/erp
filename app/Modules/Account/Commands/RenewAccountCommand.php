<?php

declare(strict_types=1);

namespace App\Modules\Account\Commands;

use App\Modules\Account\Requests\RenewAccountRequest;

class RenewAccountCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $accountId,
        public readonly string $companyId,
        public readonly float $amount,
        public readonly string $nextRenewal,
        public readonly ?string $createdBy = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(RenewAccountRequest $request, string $accountId, string $companyId, ?string $createdBy = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            accountId: $accountId,
            companyId: $companyId,
            amount: (float) $request->input('amount'),
            nextRenewal: $request->string('next_renewal')->toString(),
            createdBy: $createdBy,
            notes: $request->input('notes'),
        );
    }
}
