<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Commands;

use App\Modules\SupplierAdvance\Requests\UpdateSupplierAdvanceRequest;

class UpdateSupplierAdvanceCommand
{
    public function __construct(
        public readonly string $supplierId,
        public readonly string $advanceDate,
        public readonly float $amount,
        public readonly ?string $purchaseOrderId = null,
        public readonly string $paymentMethod = 'transfer',
        public readonly ?string $reference = null,
        public readonly ?string $bankAccount = null,
        public readonly string $currency = 'USD',
        public readonly ?string $exchangeRateOverride = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateSupplierAdvanceRequest $request): self
    {
        return new self(
            supplierId: $request->string('supplier_id')->toString(),
            advanceDate: $request->string('advance_date')->toString(),
            amount: (float) $request->input('amount', 0),
            purchaseOrderId: $request->input('purchase_order_id'),
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
