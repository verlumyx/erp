<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Commands;

use App\Modules\SupplierPayment\Requests\CreateSupplierPaymentRequest;

class CreateSupplierPaymentCommand
{
    /**
     * @param  array<int, SupplierPaymentApplicationData>  $applications
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $supplierId,
        public readonly string $paymentDate,
        public readonly string $createdBy,
        public readonly array $applications = [],
        /** `supplier` o `invoice`: el pago espejo de un anticipo no nace aquí. */
        public readonly string $originType = 'supplier',
        public readonly ?string $originId = null,
        /** De qué anticipo o nota sale el crédito, pagando sin dinero. */
        public readonly ?string $creditSourceId = null,
        public readonly string $paymentMethod = 'transfer',
        public readonly ?string $reference = null,
        public readonly ?string $bankAccount = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly float $amount = 0,
        public readonly float $withholdingAmount = 0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateSupplierPaymentRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            supplierId: $request->string('supplier_id')->toString(),
            paymentDate: $request->string('payment_date')->toString(),
            createdBy: $request->user()->id,
            applications: SupplierPaymentApplicationData::collection($request->input('applications', [])),
            originType: $request->string('origin_type', 'supplier')->toString(),
            originId: $request->input('origin_id'),
            creditSourceId: $request->input('credit_source_id'),
            paymentMethod: $request->string('payment_method', 'transfer')->toString(),
            reference: $request->input('reference'),
            bankAccount: $request->input('bank_account'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            amount: (float) $request->input('amount', 0),
            withholdingAmount: (float) $request->input('withholding_amount', 0),
            notes: $request->input('notes'),
        );
    }
}
