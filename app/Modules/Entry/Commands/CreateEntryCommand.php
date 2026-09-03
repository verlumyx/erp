<?php

declare(strict_types=1);

namespace App\Modules\Entry\Commands;

use App\Modules\Entry\Requests\CreateEntryRequest;

class CreateEntryCommand
{
    /**
     * @param  array<int, EntryLineData>  $lines
     */
    public function __construct(
        public readonly string $id,
        public readonly string $companyId,
        public readonly string $warehouseId,
        public readonly string $entryDate,
        public readonly string $createdBy,
        public readonly array $lines = [],
        /** Vacío cuando la mercancía no viene de un proveedor. */
        public readonly ?string $supplierId = null,
        /** Alias del documento origen en el morph map (`purchase_order`). */
        public readonly ?string $sourceableType = null,
        public readonly ?string $sourceableId = null,
        public readonly string $entryType = 'purchase',
        public readonly ?string $supplierDocument = null,
        public readonly ?string $carrier = null,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $receivedBy = null,
        public readonly ?string $inspectedBy = null,
        public readonly string $inspectionStatus = 'pending',
        /** El request siempre la exige: la pantalla la estrena con la de la empresa. */
        public readonly string $currency = 'USD',
        /** Corrección manual del usuario. `null` deja que la resuelva el sistema. */
        public readonly ?string $exchangeRateOverride = null,
        /** Gastos capitalizables que se prorratean al costo de las líneas. */
        public readonly ?string $notes = null,
        /**
         * El usuario tiene el permiso que deja recibir más de lo pedido. Se
         * resuelve al armar el comando, que es donde vive lo que sabe el
         * request: el servicio no consulta la sesión.
         */
        public readonly bool $allowsOverReceipt = false,
    ) {}

    public static function fromRequest(CreateEntryRequest $request, ?string $companyId = null): self
    {
        return new self(
            id: $request->string('id')->toString(),
            companyId: $companyId ?? $request->route('company'),
            warehouseId: $request->string('warehouse_id')->toString(),
            entryDate: $request->string('entry_date')->toString(),
            createdBy: $request->user()->id,
            lines: EntryLineData::collection($request->input('lines', [])),
            supplierId: $request->input('supplier_id'),
            sourceableType: $request->input('sourceable_type'),
            sourceableId: $request->input('sourceable_id'),
            entryType: $request->string('entry_type', 'purchase')->toString(),
            supplierDocument: $request->input('supplier_document'),
            carrier: $request->input('carrier'),
            trackingNumber: $request->input('tracking_number'),
            receivedBy: $request->input('received_by'),
            inspectedBy: $request->input('inspected_by'),
            inspectionStatus: $request->string('inspection_status', 'pending')->toString(),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            notes: $request->input('notes'),
            allowsOverReceipt: $request->user()?->hasPermission('entries.allow-over-receipt') ?? false,
        );
    }
}
