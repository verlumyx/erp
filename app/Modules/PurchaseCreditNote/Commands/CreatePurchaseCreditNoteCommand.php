<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Commands;

use App\Modules\PurchaseCreditNote\Requests\CreatePurchaseCreditNoteRequest;

class CreatePurchaseCreditNoteCommand
{
    /**
     * @param  array<int, PurchaseCreditNoteLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $supplierId,
        public readonly string $noteDate,
        public readonly string $createdBy,
        public readonly array $lines = [],
        /** Factura afectada. Vacía en una nota sin factura previa. */
        public readonly ?string $purchaseInvoiceId = null,
        public readonly ?string $purchaseReturnId = null,
        public readonly ?string $supplierDocumentNumber = null,
        public readonly string $reason = 'return',
        public readonly ?string $reasonDetail = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreatePurchaseCreditNoteRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            supplierId: $request->string('supplier_id')->toString(),
            noteDate: $request->string('note_date')->toString(),
            createdBy: $request->user()->id,
            lines: PurchaseCreditNoteLineData::collection($request->input('lines', [])),
            purchaseInvoiceId: $request->input('purchase_invoice_id'),
            purchaseReturnId: $request->input('purchase_return_id'),
            supplierDocumentNumber: $request->input('supplier_document_number'),
            reason: $request->string('reason', 'return')->toString(),
            reasonDetail: $request->input('reason_detail'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            notes: $request->input('notes'),
        );
    }
}
