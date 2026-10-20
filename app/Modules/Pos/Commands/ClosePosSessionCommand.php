<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

use App\Modules\Pos\Requests\ClosePosSessionRequest;

class ClosePosSessionCommand
{
    /**
     * @param  array<int, PosDenominationData>  $denominations
     */
    public function __construct(
        public readonly string $sessionId,
        public readonly string $userId,
        public readonly ?string $companyId = null,
        public readonly ?float $declaredCashUsd = null,
        public readonly ?float $declaredCashVes = null,
        public readonly ?float $declaredCardVes = null,
        public readonly ?float $declaredMobileVes = null,
        public readonly ?float $declaredOther = null,
        public readonly array $denominations = [],
        public readonly ?string $supervisorId = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(ClosePosSessionRequest $request, string $sessionId, ?string $companyId = null): self
    {
        return new self(
            sessionId: $sessionId,
            userId: $request->user()->id,
            companyId: $companyId ?? $request->route('company'),
            declaredCashUsd: $request->filled('declared_cash_usd') ? (float) $request->input('declared_cash_usd') : null,
            declaredCashVes: $request->filled('declared_cash_ves') ? (float) $request->input('declared_cash_ves') : null,
            declaredCardVes: $request->filled('declared_card_ves') ? (float) $request->input('declared_card_ves') : null,
            declaredMobileVes: $request->filled('declared_mobile_ves') ? (float) $request->input('declared_mobile_ves') : null,
            declaredOther: $request->filled('declared_other') ? (float) $request->input('declared_other') : null,
            denominations: PosDenominationData::collection($request->input('denominations', [])),
            supervisorId: $request->input('supervisor_id') ?: null,
            notes: $request->input('closing_notes') ?: $request->input('notes'),
        );
    }
}
