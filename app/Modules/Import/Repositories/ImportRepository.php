<?php

declare(strict_types=1);

namespace App\Modules\Import\Repositories;

use App\Modules\ExchangeRate\Commands\DocumentRatesData;
use App\Modules\Import\Commands\CreateImportCommand;
use App\Modules\Import\Commands\ImportCostData;
use App\Modules\Import\Commands\ImportCostingData;
use App\Modules\Import\Commands\ImportEntryData;
use App\Modules\Import\Commands\SearchImportCommand;
use App\Modules\Import\Commands\UpdateImportCommand;
use App\Modules\Import\Commands\UpdateStatusImportCommand;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Models\ImportCost;
use App\Modules\Import\Models\ImportEntry;
use App\Modules\Import\Models\ImportLine;
use App\Modules\Import\Models\ImportLineLot;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ImportRepository extends ImportFilters implements ImportRepositoryInterface
{
    public function create(CreateImportCommand $command, DocumentRatesData $rates, ImportCostingData $costing): void
    {
        DB::transaction(function () use ($command, $rates, $costing): void {
            $import = Import::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'warehouse_id' => $command->warehouseId,
                'import_date' => $command->importDate,
                'arrival_date' => $command->arrivalDate,
                'reference' => $command->reference,
                'allocation_method' => $command->allocationMethod,
                ...$rates->toAttributes(),
                ...$costing->toTotals(),
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncCosts($import, $command->costs, $costing);
            $this->syncEntries($import, $command->entries);
            $this->syncLines($import, $costing);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Import
    {
        return Import::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Import
    {
        return Import::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Import $model, UpdateImportCommand $command, DocumentRatesData $rates, ImportCostingData $costing): void
    {
        DB::transaction(function () use ($model, $command, $rates, $costing): void {
            /** El ajuste generado y la anulación no se editan aquí. */
            $model->update([
                'warehouse_id' => $command->warehouseId,
                'import_date' => $command->importDate,
                'arrival_date' => $command->arrivalDate,
                'reference' => $command->reference,
                'allocation_method' => $command->allocationMethod,
                ...$rates->toAttributes(),
                ...$costing->toTotals(),
                'notes' => $command->notes,
            ]);

            $this->syncCosts($model, $command->costs, $costing);
            $this->syncEntries($model, $command->entries);
            $this->syncLines($model, $costing);
        });
    }

    public function updateStatus(Import $model, UpdateStatusImportCommand $command): void
    {
        $attributes = ['status' => $command->status];

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
            $attributes['cancellation_reason'] = $command->cancellationReason;
        }

        $model->update($attributes);
    }

    /**
     * @return array{ data: Import[], total: int }
     */
    public function search(SearchImportCommand $command): array
    {
        $query = Import::query()
            ->with(['warehouse', 'adjustment'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('import_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, ImportLine>
     */
    public function activeLines(Import $model): array
    {
        return ImportLine::query()
            ->with(['item.units', 'measurementUnit', 'lots'])
            ->where('import_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function activeEntryIds(Import $model): array
    {
        return ImportEntry::query()
            ->where('import_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->pluck('entry_id')
            ->all();
    }

    /**
     * @param  array<int, string>  $entryIds
     * @return array<int, string>
     */
    public function entriesTakenElsewhere(
        ?string $companyId,
        array $entryIds,
        ?string $exceptImportId = null,
    ): array {
        if ($entryIds === []) {
            return [];
        }

        return ImportEntry::query()
            ->whereIn('entry_id', $entryIds)
            ->where('status', 'active')
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereHas('import', function ($query) use ($exceptImportId): void {
                $query->whereNot('status', 'cancelled')
                    ->when($exceptImportId, fn ($q) => $q->whereKeyNot($exceptImportId));
            })
            ->pluck('entry_id')
            ->unique()
            ->values()
            ->all();
    }

    public function writeCosting(Import $model, ImportCostingData $costing): Import
    {
        DB::transaction(function () use ($model, $costing): void {
            $model->update($costing->toTotals());

            $this->syncLines($model, $costing);
        });

        return $model;
    }

    public function writeAdjustment(Import $model, ?string $adjustmentId): Import
    {
        $model->update(['adjustment_id' => $adjustmentId]);

        return $model;
    }

    public function writeSettlement(Import $model, bool $settled): Import
    {
        $model->update(['status' => $settled ? 'completed' : 'confirmed']);

        return $model;
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'warehouse',
            'creator',
            'adjustment',
            'costs.supplier',
            'costs.sourceable',
            'entries.entry',
            'lines.item',
            'lines.measurementUnit',
            'lines.location',
            'lines.entryLine.entry',
            'lines.lots.lot',
        ];
    }

    /**
     * Alinea `app_import_costs` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(expediente, line_number)` es único
     * y una fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, ImportCostData>  $costs
     */
    private function syncCosts(Import $import, array $costs, ImportCostingData $costing): void
    {
        $existing = ImportCost::query()
            ->where('import_id', $import->id)
            ->get()
            ->keyBy('id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($costs as $index => $cost) {
            $current = $cost->id !== null ? $existing->get($cost->id) : null;
            $charge = $costing->charges[$index] ?? ['exchange_rate' => 1.0, 'converted_amount' => 0.0];

            $attributes = [
                'company_id' => $import->company_id,
                'sourceable_type' => $cost->sourceableType,
                'sourceable_id' => $cost->sourceableId,
                'supplier_id' => $cost->supplierId,
                'concept' => $cost->concept,
                'description' => $cost->description,
                'currency' => $cost->currency,
                'exchange_rate' => $charge['exchange_rate'],
                'amount' => $cost->amount,
                'converted_amount' => $charge['converted_amount'],
                'status' => $cost->status,
                'notes' => $cost->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = ImportCost::create([
                ...$attributes,
                'import_id' => $import->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        ImportCost::query()
            ->where('import_id', $import->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Alinea `app_import_entries`. La entrada es única dentro del expediente,
     * así que la fila se reconoce por ella y no hace falta su `id`: elegir dos
     * veces la misma recepción no crea dos filas.
     *
     * @param  array<int, ImportEntryData>  $entries
     */
    private function syncEntries(Import $import, array $entries): void
    {
        $existing = ImportEntry::query()
            ->where('import_id', $import->id)
            ->get()
            ->keyBy('entry_id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($entries as $entry) {
            $current = $existing->get($entry->entryId);

            if ($current !== null) {
                $current->update(['status' => $entry->status]);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = ImportEntry::create([
                'company_id' => $import->company_id,
                'import_id' => $import->id,
                'entry_id' => $entry->entryId,
                'line_number' => ++$nextNumber,
                'status' => $entry->status,
            ])->id;
        }

        ImportEntry::query()
            ->where('import_id', $import->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Alinea `app_import_lines` con el reparto ya resuelto.
     *
     * La fila se reconoce por su línea de entrada: los ítems no se capturan, se
     * derivan de las recepciones, así que lo que identifica a una línea entre
     * dos guardados es de dónde salió y no un id que la pantalla mande.
     */
    private function syncLines(Import $import, ImportCostingData $costing): void
    {
        $existing = ImportLine::query()
            ->where('import_id', $import->id)
            ->get()
            ->keyBy('entry_line_id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($costing->lines as $line) {
            $current = $existing->get($line['entry_line_id']);

            $attributes = [
                'company_id' => $import->company_id,
                'item_id' => $line['item_id'],
                'measurement_unit_id' => $line['measurement_unit_id'],
                'location_id' => $line['location_id'],
                'base_quantity' => $line['base_quantity'],
                'remaining_quantity' => $line['remaining_quantity'],
                'unit_cost' => $line['unit_cost'],
                'base_value' => $line['base_value'],
                'allocation_base' => $line['allocation_base'],
                'allocated_amount' => $line['allocated_amount'],
                'unit_delta' => $line['unit_delta'],
                'new_unit_cost' => $line['new_unit_cost'],
                'capitalized_amount' => $line['capitalized_amount'],
                'variance_amount' => $line['variance_amount'],
                'status' => $line['status'],
            ];

            $persisted = $current;

            if ($persisted !== null) {
                $persisted->update($attributes);
            } else {
                $persisted = ImportLine::create([
                    ...$attributes,
                    'import_id' => $import->id,
                    'entry_line_id' => $line['entry_line_id'],
                    'line_number' => ++$nextNumber,
                ]);
            }

            $keep[] = $persisted->id;
            $this->syncLineLots($persisted, $line['lots']);
        }

        ImportLine::query()
            ->where('import_id', $import->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Mismo criterio para los lotes: la fila se reconoce por la fila de lote de
     * la entrada de la que sale.
     *
     * @param  array<int, array<string, mixed>>  $lots
     */
    private function syncLineLots(ImportLine $line, array $lots): void
    {
        $existing = ImportLineLot::query()
            ->where('import_line_id', $line->id)
            ->get()
            ->keyBy('entry_line_lot_id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lots as $lot) {
            $current = $existing->get($lot['entry_line_lot_id']);

            $attributes = [
                'company_id' => $line->company_id,
                'lot_id' => $lot['lot_id'],
                'base_quantity' => $lot['base_quantity'],
                'remaining_quantity' => $lot['remaining_quantity'],
                'allocation_base' => $lot['allocation_base'],
                'allocated_amount' => $lot['allocated_amount'],
                'unit_delta' => $lot['unit_delta'],
                'new_unit_cost' => $lot['new_unit_cost'],
                'capitalized_amount' => $lot['capitalized_amount'],
                'variance_amount' => $lot['variance_amount'],
                'status' => $line->status,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = ImportLineLot::create([
                ...$attributes,
                'import_line_id' => $line->id,
                'entry_line_lot_id' => $lot['entry_line_lot_id'],
                'line_number' => ++$nextNumber,
            ])->id;
        }

        ImportLineLot::query()
            ->where('import_line_id', $line->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Generate the next sequential per-company code (IMP000001, IMP000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Import::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Import::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Import::CODE_PREFIX))) + 1
            : 1;

        return Import::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
