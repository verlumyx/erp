<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Commands;

use App\Modules\PurchaseInvoice\Requests\CreatePurchaseInvoiceRequest;

class CreatePurchaseInvoiceCommand
{
    /**
     * @param  array<int, PurchaseInvoiceLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $supplierId,
        public readonly string $warehouseId,
        public readonly string $supplierInvoiceNumber,
        public readonly string $invoiceDate,
        public readonly string $createdBy,
        public readonly array $lines = [],
        public readonly ?string $supplierInvoiceSeries = null,
        public readonly ?string $receivedDate = null,
        /** Vacío deja que se derive de los días de crédito del proveedor. */
        public readonly ?string $dueDate = null,
        /** Alias del morph map del documento origen; hoy solo `purchase_order`. */
        public readonly ?string $sourceableType = null,
        public readonly ?string $sourceableId = null,
        public readonly ?string $entryId = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly float $discountAmount = 0,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreatePurchaseInvoiceRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            supplierId: $request->string('supplier_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            supplierInvoiceNumber: $request->string('supplier_invoice_number')->toString(),
            invoiceDate: $request->string('invoice_date')->toString(),
            createdBy: $request->user()->id,
            lines: PurchaseInvoiceLineData::collection($request->input('lines', [])),
            supplierInvoiceSeries: $request->input('supplier_invoice_series'),
            receivedDate: $request->input('received_date'),
            dueDate: $request->input('due_date'),
            sourceableType: $request->input('sourceable_type'),
            sourceableId: $request->input('sourceable_id'),
            entryId: $request->input('entry_id'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            discountAmount: (float) $request->input('discount_amount', 0),
            notes: $request->input('notes'),
        );
    }
}
