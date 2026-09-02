<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Commands;

use App\Modules\SupplierAdvance\Requests\CreateSupplierAdvanceRequest;

class CreateSupplierAdvanceCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $supplierId,
        public readonly string $advanceDate,
        public readonly float $amount,
        public readonly string $createdBy,
        /** Orden que motiva el anticipo. Vacía en un anticipo sin orden previa. */
        public readonly ?string $purchaseOrderId = null,
        /** El pago cuyo excedente lo generó; nulo capturándolo a mano. */
        public readonly ?string $originPaymentId = null,
        public readonly string $paymentMethod = 'transfer',
        public readonly ?string $reference = null,
        public readonly ?string $bankAccount = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateSupplierAdvanceRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            supplierId: $request->string('supplier_id')->toString(),
            advanceDate: $request->string('advance_date')->toString(),
            amount: (float) $request->input('amount', 0),
            createdBy: $request->user()->id,
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
