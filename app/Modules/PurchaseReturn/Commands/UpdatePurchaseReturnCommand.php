<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Commands;

use App\Modules\PurchaseReturn\Requests\UpdatePurchaseReturnRequest;

class UpdatePurchaseReturnCommand
{
    /**
     * @param  array<int, PurchaseReturnLineData>  $lines
     */
    public function __construct(
        public readonly string $supplierId,
        public readonly string $warehouseId,
        public readonly string $returnDate,
        public readonly array $lines = [],
        /** Factura de origen. Vacía en una devolución sin factura previa. */
        public readonly ?string $purchaseInvoiceId = null,
        public readonly ?string $entryId = null,
        public readonly string $reason = 'damaged',
        public readonly ?string $reasonDetail = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly ?string $carrier = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdatePurchaseReturnRequest $request): self
    {
        return new self(
            supplierId: $request->string('supplier_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            returnDate: $request->string('return_date')->toString(),
            lines: PurchaseReturnLineData::collection($request->input('lines', [])),
            purchaseInvoiceId: $request->input('purchase_invoice_id'),
            entryId: $request->input('entry_id'),
            reason: $request->string('reason', 'damaged')->toString(),
            reasonDetail: $request->input('reason_detail'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            carrier: $request->input('carrier'),
            trackingNumber: $request->input('tracking_number'),
            notes: $request->input('notes'),
        );
    }
}
