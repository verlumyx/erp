<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Commands;

use App\Modules\SalesInvoice\Requests\CreateSalesInvoiceRequest;

class CreateSalesInvoiceCommand
{
    /**
     * @param  array<int, SalesInvoiceLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $clientId,
        public readonly string $warehouseId,
        public readonly string $invoiceDate,
        public readonly string $dueDate,
        public readonly string $createdBy,
        public readonly array $lines = [],
        /** Documento origen: alias del morph map + id. Nulos en factura directa. */
        public readonly ?string $sourceableType = null,
        public readonly ?string $sourceableId = null,
        public readonly ?string $dispatchId = null,
        public readonly ?string $clientAddressId = null,
        public readonly ?string $salespersonId = null,
        public readonly ?string $invoiceSeries = null,
        public readonly string $saleType = 'credit',
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(CreateSalesInvoiceRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            clientId: $request->string('client_id')->toString(),
            warehouseId: $request->string('warehouse_id')->toString(),
            invoiceDate: $request->string('invoice_date')->toString(),
            dueDate: $request->string('due_date')->toString(),
            createdBy: $request->user()->id,
            lines: SalesInvoiceLineData::collection($request->input('lines', [])),
            sourceableType: $request->input('sourceable_type'),
            sourceableId: $request->input('sourceable_id'),
            dispatchId: $request->input('dispatch_id'),
            clientAddressId: $request->input('client_address_id'),
            salespersonId: $request->input('salesperson_id'),
            invoiceSeries: $request->input('invoice_series'),
            saleType: (string) $request->input('sale_type', 'credit'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            notes: $request->input('notes'),
        );
    }
}
