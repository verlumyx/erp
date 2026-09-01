<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Commands;

use App\Modules\SalesReturn\Requests\UpdateSalesReturnRequest;

class UpdateSalesReturnCommand
{
    /**
     * @param  array<int, SalesReturnLineData>  $lines
     */
    public function __construct(
        public readonly string $clientId,
        public readonly string $warehouseId,
        public readonly string $returnDate,
        public readonly array $lines = [],
        /** Factura de origen. Vacía en una devolución sin factura previa. */
        public readonly ?string $salesInvoiceId = null,
        /** Despacho de origen. Vacío mientras el módulo de Despachos no exista. */
        public readonly ?string $dispatchId = null,
        public readonly string $reason = 'damaged',
        public readonly ?string $reasonDetail = null,
        /** En qué estado vuelve la mercancía: decide si reingresa y a dónde. */
        public readonly string $condition = 'resalable',
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        /** Quién recibió la mercancía. Vacío deja el registro sin responsable. */
        public readonly ?string $receivedBy = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateSalesReturnRequest $request): self
    {
        return new self(
            clientId: $request->string('client_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            returnDate: $request->string('return_date')->toString(),
            lines: SalesReturnLineData::collection($request->input('lines', [])),
            salesInvoiceId: $request->input('sales_invoice_id'),
            dispatchId: $request->input('dispatch_id'),
            reason: $request->string('reason', 'damaged')->toString(),
            reasonDetail: $request->input('reason_detail'),
            condition: $request->string('condition', 'resalable')->toString(),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            receivedBy: $request->input('received_by'),
            notes: $request->input('notes'),
        );
    }
}
