<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Commands;

use App\Modules\SupplierPayment\Requests\UpdateSupplierPaymentRequest;

/**
 * El origen no viaja aquí: se congela en la cabecera al crear el pago y no se
 * cambia después.
 */
class UpdateSupplierPaymentCommand
{
    /**
     * @param  array<int, SupplierPaymentApplicationData>  $applications
     */
    public function __construct(
        public readonly string $supplierId,
        public readonly string $paymentDate,
        public readonly array $applications = [],
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

    public static function fromRequest(UpdateSupplierPaymentRequest $request): self
    {
        return new self(
            supplierId: $request->string('supplier_id')->toString(),
            paymentDate: $request->string('payment_date')->toString(),
            applications: SupplierPaymentApplicationData::collection($request->input('applications', [])),
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
