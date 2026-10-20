<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

use App\Modules\Pos\Requests\CreatePosCashMovementRequest;

class CreatePosCashMovementCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $posSessionId,
        public readonly string $movementType,
        public readonly string $concept,
        public readonly string $currency,
        public readonly float $amount,
        public readonly string $reason,
        public readonly string $createdBy,
        public readonly ?string $authorizedBy = null,
        public readonly ?float $exchangeRate = null,
    ) {}

    public static function fromRequest(CreatePosCashMovementRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            posSessionId: $request->string('pos_session_id')->toString(),
            movementType: $request->string('movement_type')->toString(),
            concept: $request->string('concept')->toString(),
            currency: strtoupper($request->string('currency', 'USD')->toString()),
            amount: (float) $request->input('amount', 0),
            reason: $request->string('reason')->trim()->toString(),
            createdBy: $request->user()->id,
            authorizedBy: $request->input('authorized_by') ?: null,
            exchangeRate: $request->filled('exchange_rate') ? (float) $request->input('exchange_rate') : null,
        );
    }
}
