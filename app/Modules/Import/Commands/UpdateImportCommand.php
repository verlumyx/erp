<?php

declare(strict_types=1);

namespace App\Modules\Import\Commands;

use App\Modules\Import\Requests\UpdateImportRequest;

class UpdateImportCommand
{
    /**
     * @param  array<int, ImportCostData>  $costs
     * @param  array<int, ImportEntryData>  $entries
     * @param  array<string, string>  $lineStatuses  Id de la línea → estado.
     */
    public function __construct(
        public readonly string $warehouseId,
        public readonly string $importDate,
        public readonly array $costs = [],
        public readonly array $entries = [],
        public readonly array $lineStatuses = [],
        public readonly string $allocationMethod = 'value',
        public readonly ?string $arrivalDate = null,
        public readonly ?string $reference = null,
        public readonly string $currency = 'USD',
        public readonly ?string $exchangeRateOverride = null,
        public readonly ?string $notes = null,
    ) {}

    public static function fromRequest(UpdateImportRequest $request): self
    {
        return new self(
            warehouseId: $request->string('warehouse_id')->toString(),
            importDate: $request->string('import_date')->toString(),
            costs: ImportCostData::collection($request->input('costs', [])),
            entries: ImportEntryData::collection($request->input('entries', [])),
            lineStatuses: ImportLineStatusData::map($request->input('lines', [])),
            allocationMethod: $request->string('allocation_method', 'value')->toString(),
            arrivalDate: $request->input('arrival_date'),
            reference: $request->input('reference'),
            currency: strtoupper($request->string('currency')->toString()),
            exchangeRateOverride: $request->filled('exchange_rate')
                ? (string) $request->input('exchange_rate')
                : null,
            notes: $request->input('notes'),
        );
    }
}
