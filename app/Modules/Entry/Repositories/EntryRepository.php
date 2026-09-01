<?php

declare(strict_types=1);

namespace App\Modules\Entry\Repositories;

use App\Modules\Entry\Commands\CreateEntryCommand;
use App\Modules\Entry\Commands\EntryLineData;
use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Commands\UpdateEntryCommand;
use App\Modules\Entry\Commands\UpdateStatusEntryCommand;
use App\Modules\Entry\Commands\WriteEntryLineTraceabilityCommand;
use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Models\EntryLine;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EntryRepository extends EntryFilters implements EntryRepositoryInterface
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    public function create(CreateEntryCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($command, $rates): void {
            $costs = $this->lineCosts($command->companyId, $command->lines, $command->freightAmount + $command->otherCharges);

            $entry = Entry::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'supplier_id' => $command->supplierId,
                'sourceable_type' => $command->sourceableType,
                'sourceable_id' => $command->sourceableId,
                'warehouse_id' => $command->warehouseId,
                'entry_date' => $command->entryDate,
                'entry_type' => $command->entryType,
                'supplier_document' => $command->supplierDocument,
                'carrier' => $command->carrier,
                'tracking_number' => $command->trackingNumber,
                'received_by' => $command->receivedBy,
                'inspected_by' => $command->inspectedBy,
                'inspection_status' => $command->inspectionStatus,
                ...$rates->toAttributes(),
                'freight_amount' => $command->freightAmount,
                'other_charges' => $command->otherCharges,
                ...$this->totals($command->lines, $costs),
                'is_invoiced' => 'no',
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($entry, $command->lines, $costs);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Entry
    {
        return Entry::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Entry
    {
        return Entry::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Entry $model, UpdateEntryCommand $command, DocumentRatesData $rates): void
    {
        DB::transaction(function () use ($model, $command, $rates): void {
            $costs = $this->lineCosts($model->company_id, $command->lines, $command->freightAmount + $command->otherCharges);

            /** La marca de facturado y la de anulación no se editan aquí. */
            $model->update([
                'supplier_id' => $command->supplierId,
                'sourceable_type' => $command->sourceableType,
                'sourceable_id' => $command->sourceableId,
                'warehouse_id' => $command->warehouseId,
                'entry_date' => $command->entryDate,
                'entry_type' => $command->entryType,
                'supplier_document' => $command->supplierDocument,
                'carrier' => $command->carrier,
                'tracking_number' => $command->trackingNumber,
                'received_by' => $command->receivedBy,
                'inspected_by' => $command->inspectedBy,
                'inspection_status' => $command->inspectionStatus,
                ...$rates->toAttributes(),
                'freight_amount' => $command->freightAmount,
                'other_charges' => $command->otherCharges,
                ...$this->totals($command->lines, $costs),
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $command->lines, $costs);
        });
    }

    public function updateStatus(Entry $model, UpdateStatusEntryCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
        }

        $model->update($attributes);
    }

    /**
     * @return array{ data: Entry[], total: int }
     */
    public function search(SearchEntryCommand $command): array
    {
        $query = Entry::query()
            ->with(['supplier', 'warehouse'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('entry_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, EntryLine>
     */
    public function activeLines(Entry $model): array
    {
        return EntryLine::query()
            ->with(['item', 'measurementUnit'])
            ->where('entry_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
    }

    public function writeLineTraceability(
        EntryLine $line,
        WriteEntryLineTraceabilityCommand $command,
    ): EntryLine {
        $line->update([
            'lot_id' => $command->lotId,
            'lot_number' => $command->lotNumber,
        ]);

        return $line;
    }

    /**
     * Cantidad ya recibida de cada línea de documento origen.
     *
     * Suma solo las líneas activas de entradas que siguen vivas: una entrada
     * anulada devuelve su cupo, y la que se está guardando no compite consigo
     * misma.
     *
     * @param  array<int, string>  $sourceLineIds
     * @return array<string, float>
     */
    public function receivedQuantities(array $sourceLineIds, ?string $exceptEntryId = null): array
    {
        if ($sourceLineIds === []) {
            return [];
        }

        return EntryLine::query()
            ->whereIn('sourceable_id', $sourceLineIds)
            ->where('app_entry_lines.status', 'active')
            ->when($exceptEntryId, fn ($q) => $q->where('entry_id', '!=', $exceptEntryId))
            ->whereHas('entry', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->groupBy('sourceable_id')
            ->selectRaw('sourceable_id, SUM(received_quantity) as received')
            ->pluck('received', 'sourceable_id')
            ->map(fn ($received): float => (float) $received)
            ->all();
    }

    /**
     * @param  array<int, string>  $itemIds
     * @return array<int, string>
     */
    public function itemsWithInitialEntry(
        ?string $companyId,
        string $warehouseId,
        array $itemIds,
        ?string $exceptEntryId = null,
    ): array {
        if ($itemIds === []) {
            return [];
        }

        return EntryLine::query()
            ->whereIn('item_id', $itemIds)
            ->where('app_entry_lines.status', 'active')
            ->when($exceptEntryId, fn ($q) => $q->where('entry_id', '!=', $exceptEntryId))
            ->whereHas('entry', function ($q) use ($companyId, $warehouseId): void {
                $q->where('entry_type', Entry::INITIAL_TYPE)
                    ->where('warehouse_id', $warehouseId)
                    ->where('status', '!=', 'cancelled')
                    ->when($companyId, fn ($query) => $query->where('company_id', $companyId));
            })
            ->distinct()
            ->pluck('item_id')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'supplier',
            'sourceable',
            'warehouse',
            'receiver',
            'inspector',
            'lines.item',
            'lines.measurementUnit',
            'lines.location',
            'lines.lot',
        ];
    }

    /**
     * Alinea `app_entry_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(entrada, line_number)` es único y
     * una fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, EntryLineData>  $lines
     * @param  array<int, array{factor: float, base_quantity: float, base_received: float, unit_cost: float, landed_cost: float}>  $costs
     */
    private function syncLines(Entry $entry, array $lines, array $costs): void
    {
        $existing = EntryLine::query()
            ->where('entry_id', $entry->id)
            ->get()
            ->keyBy('id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $index => $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;
            $cost = $costs[$index];

            $attributes = [
                'company_id' => $entry->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'sourceable_type' => $line->sourceableType,
                'sourceable_id' => $line->sourceableId,
                'location_id' => $line->locationId,
                'lot_number' => $line->lotNumber,
                /** El lote resuelto es del confirmado: en borrador solo se conserva. */
                'lot_id' => $line->lotId ?? $current?->lot_id,
                'expires_at' => $line->expiresAt,
                'serial_numbers' => $line->serialNumbers === [] ? null : $line->serialNumbers,
                'quantity' => $line->quantity,
                'base_quantity' => $cost['base_quantity'],
                'unit_price' => $line->unitPrice,
                'discount_percent' => $line->discountPercent,
                'discount_amount' => $line->discountAmount,
                'tax_id' => $line->taxId,
                'tax_percent' => $line->taxPercent,
                'tax_amount' => $line->taxAmount,
                'withholding_percent' => $line->withholdingPercent,
                'withholding_amount' => $line->withholdingAmount,
                'subtotal' => $line->subtotal,
                'total' => $line->total,
                'received_quantity' => $line->receivedQuantity,
                'rejected_quantity' => $line->rejectedQuantity,
                'unit_cost' => $cost['unit_cost'],
                'landed_cost' => $cost['landed_cost'],
                'rejection_reason' => $line->rejectionReason,
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = EntryLine::create([
                ...$attributes,
                'entry_id' => $entry->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        EntryLine::query()
            ->where('entry_id', $entry->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Costo de cada línea, en la unidad base y con los gastos ya repartidos.
     *
     * `unit_cost` es lo que la línea vale por unidad base antes de prorrateos:
     * el subtotal —ya neto de descuento— dividido entre la cantidad en unidad
     * base. `landed_cost` le añade la parte que le toca del flete y de los
     * otros gastos.
     *
     * El reparto es **por valor de línea**: cada línea carga con los gastos en
     * la misma proporción en que aporta valor a lo recibido, lo que equivale a
     * multiplicar su costo por un mismo factor. Solo lo aceptado entra en el
     * reparto —lo rechazado no ingresa al inventario, así que no absorbe
     * gastos— y unos gastos sin valor sobre el que repartirse se quedan fuera
     * del costo en vez de inventarse uno.
     *
     * @param  array<int, EntryLineData>  $lines
     * @return array<int, array{factor: float, base_quantity: float, base_received: float, unit_cost: float, landed_cost: float}>
     */
    private function lineCosts(?string $companyId, array $lines, float $charges): array
    {
        $factors = $this->conversionFactors($companyId, $lines);
        $costs = [];
        $receivedValue = 0.0;

        foreach ($lines as $index => $line) {
            $factor = $factors[$line->itemId.'|'.$line->measurementUnitId] ?? 1.0;

            $baseQuantity = round($line->quantity * $factor, 4);
            $baseReceived = round($line->receivedQuantity * $factor, 4);

            $unitCost = $baseQuantity > 0.0
                ? round($line->subtotal / $baseQuantity, 6)
                : 0.0;

            $costs[$index] = [
                'factor' => $factor,
                'base_quantity' => $baseQuantity,
                'base_received' => $baseReceived,
                'unit_cost' => $unitCost,
                'landed_cost' => $unitCost,
            ];

            if ($line->status === 'active') {
                $receivedValue += $unitCost * $baseReceived;
            }
        }

        $ratio = $receivedValue > 0.0 ? round($charges / $receivedValue, 10) : 0.0;

        if ($ratio === 0.0) {
            return $costs;
        }

        foreach ($costs as $index => $cost) {
            $costs[$index]['landed_cost'] = round($cost['unit_cost'] * (1 + $ratio), 6);
        }

        return $costs;
    }

    /**
     * Factor de conversión a la unidad base por par artículo/unidad.
     *
     * Se resuelve a través del repositorio de artículos: el módulo de entradas
     * nunca consulta las tablas del módulo de inventario directamente. Un par
     * sin unidad registrada cae en 1, que es lo que valida el Request.
     *
     * @param  array<int, EntryLineData>  $lines
     * @return array<string, float>
     */
    private function conversionFactors(?string $companyId, array $lines): array
    {
        $factors = [];

        foreach (array_unique(array_map(fn (EntryLineData $line): string => $line->itemId, $lines)) as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            if (! $item instanceof Item) {
                continue;
            }

            foreach ($item->units as $unit) {
                /** @var ItemUnit $unit */
                $factors[$itemId.'|'.$unit->measurement_unit_id] = (float) $unit->conversion_factor;
            }
        }

        return $factors;
    }

    /**
     * Totales de la cabecera. Suman **solo** las líneas activas.
     *
     * `total_quantity` es lo aceptado en unidad base —lo rechazado no entró— y
     * `total_cost` es su valor ya con los gastos dentro, que es exactamente el
     * valor que el kardex va a recibir.
     *
     * @param  array<int, EntryLineData>  $lines
     * @param  array<int, array{base_received: float, landed_cost: float}>  $costs
     * @return array<string, float>
     */
    private function totals(array $lines, array $costs): array
    {
        $quantity = 0.0;
        $cost = 0.0;

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active') {
                continue;
            }

            $quantity += $costs[$index]['base_received'];
            $cost += round($costs[$index]['landed_cost'] * $costs[$index]['base_received'], 2);
        }

        return [
            'total_quantity' => round($quantity, 4),
            'total_cost' => round($cost, 2),
        ];
    }

    /**
     * Generate the next sequential per-company code (ENT000001, ENT000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Entry::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Entry::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Entry::CODE_PREFIX))) + 1
            : 1;

        return Entry::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
