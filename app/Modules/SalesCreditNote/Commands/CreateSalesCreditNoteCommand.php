<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Commands;

use App\Modules\SalesCreditNote\Requests\CreateSalesCreditNoteRequest;

class CreateSalesCreditNoteCommand
{
    /**
     * @param  array<int, SalesCreditNoteLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $clientId,
        public readonly string $noteDate,
        public readonly string $createdBy,
        public readonly array $lines = [],
        /** Factura afectada. Vacía en una nota sin factura previa. */
        public readonly ?string $salesInvoiceId = null,
        /** Devolución que origina la nota, cuando nace de una. */
        public readonly ?string $salesReturnId = null,
        public readonly ?string $noteSeries = null,
        public readonly string $reason = 'return',
        public readonly ?string $reasonDetail = null,
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateSalesCreditNoteRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            clientId: $request->string('client_id')->toString(),
            noteDate: $request->string('note_date')->toString(),
            createdBy: $request->user()->id,
            lines: SalesCreditNoteLineData::collection($request->input('lines', [])),
            salesInvoiceId: $request->input('sales_invoice_id'),
            salesReturnId: $request->input('sales_return_id'),
            noteSeries: $request->input('note_series'),
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
