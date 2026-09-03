<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Commands;

use App\Modules\SalesCreditNote\Requests\UpdateSalesCreditNoteRequest;

class UpdateSalesCreditNoteCommand
{
    /**
     * @param  array<int, SalesCreditNoteLineData>  $lines
     */
    public function __construct(
        public readonly string $clientId,
        public readonly string $noteDate,
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

    public static function fromRequest(UpdateSalesCreditNoteRequest $request): self
    {
        return new self(
            clientId: $request->string('client_id')->toString(),
            noteDate: $request->string('note_date')->toString(),
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
