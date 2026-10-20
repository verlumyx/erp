<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

use App\Modules\Pos\Requests\OpenPosSessionRequest;

class OpenPosSessionCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $posRegisterId,
        public readonly string $userId,
        public readonly float $openingCashUsd = 0.0,
        public readonly float $openingCashVes = 0.0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(OpenPosSessionRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            posRegisterId: $request->string('pos_register_id')->toString(),
            userId: $request->user()->id,
            openingCashUsd: (float) $request->input('opening_cash_usd', 0),
            openingCashVes: (float) $request->input('opening_cash_ves', 0),
            notes: $request->input('notes') ?: null,
        );
    }
}
