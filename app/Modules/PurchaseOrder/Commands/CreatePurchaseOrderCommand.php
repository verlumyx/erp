<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Commands;

use App\Modules\PurchaseOrder\Requests\CreatePurchaseOrderRequest;

class CreatePurchaseOrderCommand
{
    /**
     * @param  array<int, PurchaseOrderLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $supplierId,
        public readonly string $warehouseId,
        public readonly string $orderDate,
        public readonly string $createdBy,
        public readonly array $lines = [],
        public readonly ?string $expectedDate = null,
        public readonly ?string $supplierReference = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly int $paymentTermDays = 0,
        public readonly float $discountAmount = 0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreatePurchaseOrderRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            supplierId: $request->string('supplier_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            orderDate: $request->string('order_date')->toString(),
            createdBy: $request->user()->id,
            lines: PurchaseOrderLineData::collection($request->input('lines', [])),
            expectedDate: $request->input('expected_date'),
            supplierReference: $request->input('supplier_reference'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            paymentTermDays: $request->integer('payment_term_days'),
            discountAmount: (float) $request->input('discount_amount', 0),
            notes: $request->input('notes'),
        );
    }
}
