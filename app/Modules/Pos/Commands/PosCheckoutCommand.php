<?php

declare(strict_types=1);

namespace App\Modules\Pos\Commands;

use App\Modules\Pos\Requests\PosCheckoutRequest;
use App\Modules\SalesInvoice\Commands\SalesInvoiceLineData;

class PosCheckoutCommand
{
    /**
     * @param  array<int, PosSaleLineData>  $lines
     * @param  array<int, PosPaymentData>  $payments
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
        public readonly array $payments = [],
        public readonly ?float $exchangeRate = null,
        public readonly float $subtotal = 0.0,
        public readonly float $discountAmount = 0.0,
        public readonly float $taxAmount = 0.0,
        public readonly float $igtfAmount = 0.0,
        public readonly float $total = 0.0,
        public readonly float $paidAmount = 0.0,
        public readonly float $changeAmountUsd = 0.0,
        public readonly float $changeAmountVes = 0.0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(PosCheckoutRequest $request, ?string $companyId = null): self
    {
        $currency = strtoupper($request->string('currency', 'USD')->toString());
        $rate = $request->filled('exchange_rate') ? (float) $request->input('exchange_rate') : 1.0;

        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            posSessionId: $request->string('pos_session_id')->toString(),
            userId: $request->user()->id,
            clientId: $request->string('client_id')->toString(),
            saleDate: $request->string('sale_date', now()->toDateTimeString())->toString(),
            currency: $currency,
            lines: PosSaleLineData::collection($request->input('lines', [])),
            payments: PosPaymentData::collection($request->input('payments', []), $rate, $currency),
            exchangeRate: $request->filled('exchange_rate') ? (float) $request->input('exchange_rate') : null,
            subtotal: (float) $request->input('subtotal', 0),
            discountAmount: (float) $request->input('discount_amount', 0),
            taxAmount: (float) $request->input('tax_amount', 0),
            igtfAmount: (float) $request->input('igtf_amount', 0),
            total: (float) $request->input('total', 0),
            paidAmount: (float) $request->input('paid_amount', 0),
            changeAmountUsd: (float) $request->input('change_amount_usd', 0),
            changeAmountVes: (float) $request->input('change_amount_ves', 0),
            notes: $request->input('notes') ?: null,
        );
    }

    /**
     * @return array<int, SalesInvoiceLineData>
     */
    public function toInvoiceLines(): array
    {
        return array_map(
            static fn (PosSaleLineData $line): SalesInvoiceLineData => $line->toInvoiceLine(),
            $this->lines
        );
    }
}
