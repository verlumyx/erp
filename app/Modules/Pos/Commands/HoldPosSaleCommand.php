<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

use App\Modules\Pos\Requests\HoldPosSaleRequest;

class HoldPosSaleCommand
{
    /**
     * @param  array<int, PosSaleLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $posSessionId,
        public readonly string $userId,
        public readonly string $clientId,
        public readonly string $saleDate,
        public readonly string $currency,
        public readonly array $lines = [],
        public readonly float $subtotal = 0.0,
        public readonly float $discountAmount = 0.0,
        public readonly float $taxAmount = 0.0,
        public readonly float $total = 0.0,
        public readonly ?float $exchangeRate = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(HoldPosSaleRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            posSessionId: $request->string('pos_session_id')->toString(),
            userId: $request->user()->id,
            clientId: $request->string('client_id')->toString(),
            saleDate: $request->string('sale_date', now()->toDateTimeString())->toString(),
            currency: strtoupper($request->string('currency', 'USD')->toString()),
            lines: PosSaleLineData::collection($request->input('lines', [])),
            subtotal: (float) $request->input('subtotal', 0),
            discountAmount: (float) $request->input('discount_amount', 0),
            taxAmount: (float) $request->input('tax_amount', 0),
            total: (float) $request->input('total', 0),
            exchangeRate: $request->filled('exchange_rate') ? (float) $request->input('exchange_rate') : null,
            notes: $request->input('notes') ?: null,
        );
    }
}
