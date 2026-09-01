<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Commands;

use App\Modules\ClientAdvance\Requests\UpdateClientAdvanceRequest;

class UpdateClientAdvanceCommand
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $advanceDate,
        public readonly float $amount,
        public readonly ?string $salesOrderId = null,
        public readonly string $paymentMethod = 'transfer',
        public readonly ?string $reference = null,
        public readonly ?string $bankAccount = null,
        public readonly string $currency = 'USD',
        public readonly ?string $exchangeRateOverride = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateClientAdvanceRequest $request): self
    {
        return new self(
            clientId: $request->string('client_id')->toString(),
            advanceDate: $request->string('advance_date')->toString(),
            amount: (float) $request->input('amount', 0),
            salesOrderId: $request->input('sales_order_id'),
            paymentMethod: $request->string('payment_method', 'transfer')->toString(),
            reference: $request->input('reference'),
            bankAccount: $request->input('bank_account'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            notes: $request->input('notes'),
        );
    }
}
