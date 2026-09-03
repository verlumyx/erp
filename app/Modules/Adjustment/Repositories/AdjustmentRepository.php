<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Repositories;

use App\Modules\Adjustment\Commands\AdjustmentLineData;
use App\Modules\Adjustment\Commands\AdjustmentLineLotData;
use App\Modules\Adjustment\Commands\CreateAdjustmentCommand;
use App\Modules\Adjustment\Commands\SearchAdjustmentCommand;
use App\Modules\Adjustment\Commands\UpdateAdjustmentCommand;
use App\Modules\Adjustment\Commands\UpdateStatusAdjustmentCommand;
use App\Modules\Adjustment\Commands\WriteAdjustmentLineCostCommand;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Adjustment\Models\AdjustmentLineLot;
use App\Modules\Adjustment\Models\AdjustmentLineSerial;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AdjustmentRepository extends AdjustmentFilters implements AdjustmentRepositoryInterface
{
    public function create(CreateAdjustmentCommand $command, array $stock): void
    {
        DB::transaction(function () use ($command, $stock): void {
            $rows = $this->lineRows($command->type, $command->lines, $stock);

            $adjustment = Adjustment::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'warehouse_id' => $command->warehouseId,
                'adjustment_date' => $command->adjustmentDate,
                'type' => $command->type,
                'direction' => $command->direction,
                'reason' => $command->reason,
                'count_id' => $command->countId,
                ...$this->totals($command->lines, $rows),
                'attachment_path' => $command->attachmentPath,
                'notes' => $command->notes,
                'status' => 'draft',
                'created_by' => $command->createdBy,
            ]);

            $this->syncLines($adjustment, $command->lines, $rows);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Adjustment
    {
        return Adjustment::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Adjustment
    {
        return Adjustment::query()
            ->with($this->detailRelations())
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Adjustment $model, UpdateAdjustmentCommand $command, array $stock): void
    {
        DB::transaction(function () use ($model, $command, $stock): void {
            $rows = $this->lineRows($command->type, $command->lines, $stock);

            /** La aprobación y la anulación no se editan aquí. */
            $model->update([
                'warehouse_id' => $command->warehouseId,
                'adjustment_date' => $command->adjustmentDate,
                'type' => $command->type,
                'direction' => $command->direction,
                'reason' => $command->reason,
                'count_id' => $command->countId,
                ...$this->totals($command->lines, $rows),
                'attachment_path' => $command->attachmentPath,
                'notes' => $command->notes,
            ]);

            $this->syncLines($model, $command->lines, $rows);
        });
    }

    public function updateStatus(Adjustment $model, UpdateStatusAdjustmentCommand $command): void
    {
        $attributes = ['status' => $command->status];

        /** Confirmar es el momento en que alguien firma que el ajuste se aplica. */
        if ($command->status === 'confirmed') {
            $attributes['approved_by'] = $command->approvedBy;
            $attributes['approved_at'] = now();
        }

        if ($command->status === 'cancelled') {
            $attributes['cancelled_at'] = now();
            $attributes['cancellation_reason'] = $command->cancellationReason;
        }

        $model->update($attributes);
    }

    /**
     * @return array{ data: Adjustment[], total: int }
     */
    public function search(SearchAdjustmentCommand $command): array
    {
        $query = Adjustment::query()
            ->with(['warehouse', 'approver'])
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('adjustment_date')
            ->orderByDesc('code')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * @return array<int, AdjustmentLine>
     */
    public function activeLines(Adjustment $model): array
    {
        return AdjustmentLine::query()
            ->with(['item.units', 'measurementUnit', 'lots', 'serials.adjustmentLineLot'])
            ->where('adjustment_id', $model->id)
            ->where('status', 'active')
            ->orderBy('line_number')
            ->get()
            ->all();
    }

    public function writeLineCost(
        AdjustmentLine $line,
        WriteAdjustmentLineCostCommand $command,
    ): AdjustmentLine {
        $line->update([
            'unit_cost' => round($command->unitCost, 6),
            'total_cost' => round($command->totalCost, 2),
        ]);

        return $line;
    }

    public function writeLotCost(
        AdjustmentLineLot $lot,
        WriteAdjustmentLineCostCommand $command,
    ): AdjustmentLineLot {
        $lot->update([
            'unit_cost' => round($command->unitCost, 6),
            'total_cost' => round($command->totalCost, 2),
        ]);

        return $lot;
    }

    /**
     * Totales de la cabecera a partir de lo ya escrito.
     *
     * Suma por lote cuando la línea los tiene: una misma línea puede traer un
     * lote con sobrante y otro con faltante, y sumarla por su neto escondería
     * uno de los dos lados.
     */
    public function refreshTotals(Adjustment $model): Adjustment
    {
        $lines = AdjustmentLine::query()
            ->with('lots')
            ->where('adjustment_id', $model->id)
            ->where('status', 'active')
            ->get();

        $quantityIn = 0.0;
        $quantityOut = 0.0;
        $costIn = 0.0;
        $costOut = 0.0;

        foreach ($lines as $line) {
            $lots = $line->lots->where('status', 'active');

            $movements = $lots->isNotEmpty()
                ? $lots->map(static fn (AdjustmentLineLot $lot): array => [
                    'quantity' => abs((float) $lot->base_quantity),
                    'cost' => (float) $lot->total_cost,
                    'type' => $lot->movement_type,
                ])->all()
                : [[
                    'quantity' => abs((float) $line->base_quantity),
                    'cost' => (float) $line->total_cost,
                    'type' => $line->movement_type,
                ]];

            foreach ($movements as $movement) {
                if ($movement['type'] === AdjustmentLine::MOVEMENT_IN) {
                    $quantityIn += $movement['quantity'];
                    $costIn += $movement['cost'];

                    continue;
                }

                $quantityOut += $movement['quantity'];
                $costOut += $movement['cost'];
            }
        }

        $model->update([
            'total_quantity_in' => round($quantityIn, 4),
            'total_quantity_out' => round($quantityOut, 4),
            'total_cost_in' => round($costIn, 2),
            'total_cost_out' => round($costOut, 2),
            'net_cost' => round($costIn - $costOut, 2),
        ]);

        return $model;
    }

    /**
     * @return array<int, string>
     */
    private function detailRelations(): array
    {
        return [
            'warehouse',
            'approver',
            'creator',
            'lines.item',
            'lines.measurementUnit',
            'lines.location',
            'lines.lots.lot',
            'lines.serials.serial',
            'lines.serials.adjustmentLineLot',
            'lines.counter',
        ];
    }

    /**
     * Lo que cada línea guarda además de lo que se capturó: la diferencia, su
     * conversión a unidad base, la dirección del movimiento y el costo.
     *
     * En un ajuste corriente la diferencia manda: su signo decide la dirección
     * y el costo es el promedio vigente de lo que se está contando. En una
     * revaluación no se mueve nada —lo contado es igual a lo que dice el
     * sistema—, así que lo que decide la dirección es el costo: el que sube
     * el valor del inventario carga, el que lo baja descarga.
     *
     * Cuando la línea cuenta lotes, cada uno se resuelve igual y por separado
     * —tiene su propia existencia, su propia diferencia y su propio promedio— y
     * la línea es la suma de los suyos. Es lo que hace que el kardex pueda
     * escribir un movimiento por lote.
     *
     * @param  array<int, AdjustmentLineData>  $lines
     * @param  array<int, array{factor: float, system: float, base_system: float, average: float, lots?: array<string, array{system: float, base_system: float, average: float}>}>  $stock
     * @return array<int, array{system: float, difference: float, base_quantity: float, movement_type: string, unit_cost: float, total_cost: float, lots: array<string, array{counted: float, system: float, difference: float, base_quantity: float, movement_type: string, unit_cost: float, total_cost: float}>}>
     */
    private function lineRows(string $type, array $lines, array $stock): array
    {
        $rows = [];

        foreach ($lines as $index => $line) {
            $resolved = $stock[$index] ?? ['factor' => 1.0, 'system' => 0.0, 'base_system' => 0.0, 'average' => 0.0];

            $lots = $this->lotRows($type, $line, $resolved);

            $rows[$index] = [
                ...$lots === []
                    ? $this->balanceRow($type, $line->countedQuantity, $line->unitCost, $resolved)
                    : $this->aggregateOfLots($type, $line, $resolved, $lots),
                'lots' => $lots,
            ];
        }

        return $rows;
    }

    /**
     * Lo que guarda cada lote contado, con su propia existencia detrás.
     *
     * @param  array{factor: float, system: float, base_system: float, average: float, lots?: array<string, array{system: float, base_system: float, average: float}>}  $resolved
     * @return array<string, array{counted: float, system: float, difference: float, base_quantity: float, movement_type: string, unit_cost: float, total_cost: float}>
     */
    private function lotRows(string $type, AdjustmentLineData $line, array $resolved): array
    {
        $rows = [];

        foreach ($line->activeLots() as $lot) {
            /** @var AdjustmentLineLotData $lot */
            $balance = $resolved['lots'][$lot->lotId] ?? ['system' => 0.0, 'base_system' => 0.0, 'average' => 0.0];

            $rows[$lot->lotId] = [
                'counted' => $lot->countedQuantity,
                ...$this->balanceRow($type, $lot->countedQuantity, $line->unitCost, [
                    'factor' => $resolved['factor'],
                    ...$balance,
                ]),
            ];
        }

        return $rows;
    }

    /**
     * El cálculo de una existencia contada, que es el mismo para la línea
     * entera y para cada uno de sus lotes.
     *
     * @param  array{factor: float, system: float, base_system: float, average: float}  $resolved
     * @return array{system: float, difference: float, base_quantity: float, movement_type: string, unit_cost: float, total_cost: float}
     */
    private function balanceRow(string $type, float $counted, ?float $newCost, array $resolved): array
    {
        $system = round($resolved['system'], 4);

        if ($type === Adjustment::REVALUATION_TYPE) {
            $unitCost = round($newCost ?? $resolved['average'], 6);
            $delta = round($unitCost - $resolved['average'], 6);

            return [
                'system' => $system,
                /** Una revaluación no mueve cantidad: solo reexpresa la que ya está. */
                'difference' => 0.0,
                'base_quantity' => 0.0,
                'movement_type' => $delta < 0.0
                    ? AdjustmentLine::MOVEMENT_OUT
                    : AdjustmentLine::MOVEMENT_IN,
                'unit_cost' => $unitCost,
                'total_cost' => round(abs($resolved['base_system'] * $delta), 2),
            ];
        }

        $difference = round($counted - $system, 4);
        $baseQuantity = round($difference * $resolved['factor'], 4);
        $unitCost = round($resolved['average'], 6);

        return [
            'system' => $system,
            'difference' => $difference,
            'base_quantity' => $baseQuantity,
            'movement_type' => $difference < 0.0
                ? AdjustmentLine::MOVEMENT_OUT
                : AdjustmentLine::MOVEMENT_IN,
            'unit_cost' => $unitCost,
            'total_cost' => round(abs($baseQuantity) * $unitCost, 2),
        ];
    }

    /**
     * La línea que cuenta lotes es la suma de los suyos. Su dirección sale del
     * neto —lo que el documento acaba haciendo con el valor del inventario—,
     * aunque cada lote conserve la suya para el kardex y para los totales.
     *
     * @param  array{factor: float, system: float, base_system: float, average: float}  $resolved
     * @param  array<string, array{system: float, difference: float, base_quantity: float, movement_type: string, unit_cost: float, total_cost: float}>  $lots
     * @return array{system: float, difference: float, base_quantity: float, movement_type: string, unit_cost: float, total_cost: float}
     */
    private function aggregateOfLots(string $type, AdjustmentLineData $line, array $resolved, array $lots): array
    {
        $totalCost = round(array_sum(array_column($lots, 'total_cost')), 2);

        $signedCost = round(array_sum(array_map(
            static fn (array $lot): float => $lot['movement_type'] === AdjustmentLine::MOVEMENT_IN
                ? $lot['total_cost']
                : -$lot['total_cost'],
            $lots,
        )), 2);

        if ($type === Adjustment::REVALUATION_TYPE) {
            return [
                'system' => round(array_sum(array_column($lots, 'system')), 4),
                'difference' => 0.0,
                'base_quantity' => 0.0,
                'movement_type' => $signedCost < 0.0
                    ? AdjustmentLine::MOVEMENT_OUT
                    : AdjustmentLine::MOVEMENT_IN,
                'unit_cost' => round($line->unitCost ?? $resolved['average'], 6),
                'total_cost' => $totalCost,
            ];
        }

        $baseQuantity = round(array_sum(array_column($lots, 'base_quantity')), 4);

        return [
            'system' => round(array_sum(array_column($lots, 'system')), 4),
            'difference' => round(array_sum(array_column($lots, 'difference')), 4),
            'base_quantity' => $baseQuantity,
            'movement_type' => $signedCost < 0.0
                ? AdjustmentLine::MOVEMENT_OUT
                : AdjustmentLine::MOVEMENT_IN,
            /** El promedio de la línea es el de sus lotes, ya ponderado. */
            'unit_cost' => round($resolved['average'], 6),
            'total_cost' => $totalCost,
        ];
    }

    /**
     * Alinea `app_adjustment_lines` con lo enviado desde la pantalla.
     *
     * Las filas existentes se reconocen por su `id` y conservan su
     * `line_number`; las nuevas toman el siguiente número libre. Las que dejan
     * de venir no se borran, se desactivan (política de no borrado), y por eso
     * los números no se recalculan: el par `(ajuste, line_number)` es único y
     * una fila inactiva sigue ocupando el suyo.
     *
     * @param  array<int, AdjustmentLineData>  $lines
     * @param  array<int, array{system: float, difference: float, base_quantity: float, movement_type: string, unit_cost: float, total_cost: float, lots: array<string, array<string, mixed>>}>  $rows
     */
    private function syncLines(Adjustment $adjustment, array $lines, array $rows): void
    {
        $existing = AdjustmentLine::query()
            ->where('adjustment_id', $adjustment->id)
            ->get()
            ->keyBy('id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($lines as $index => $line) {
            $current = $line->id !== null ? $existing->get($line->id) : null;
            $row = $rows[$index];

            $attributes = [
                'company_id' => $adjustment->company_id,
                'item_id' => $line->itemId,
                'measurement_unit_id' => $line->measurementUnitId,
                'location_id' => $line->locationId,
                'system_quantity' => $row['system'],
                'counted_quantity' => $line->countedQuantity,
                'difference_quantity' => $row['difference'],
                'base_quantity' => $row['base_quantity'],
                'movement_type' => $row['movement_type'],
                'unit_cost' => $row['unit_cost'],
                'total_cost' => $row['total_cost'],
                'reason' => $line->reason,
                'counted_by' => $line->countedBy,
                'status' => $line->status,
                'notes' => $line->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;
                $this->syncLineTraceability($current, $line, $row['lots']);

                continue;
            }

            $persisted = AdjustmentLine::create([
                ...$attributes,
                'adjustment_id' => $adjustment->id,
                'line_number' => ++$nextNumber,
            ]);

            $keep[] = $persisted->id;
            $this->syncLineTraceability($persisted, $line, $row['lots']);
        }

        AdjustmentLine::query()
            ->where('adjustment_id', $adjustment->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Alinea los lotes y las series de una línea con lo enviado.
     *
     * Mismo criterio que las líneas: se reconocen por `id`, conservan su
     * `line_number` y las que dejan de venir se desactivan.
     *
     * @param  array<string, array<string, mixed>>  $lotRows
     */
    private function syncLineTraceability(AdjustmentLine $line, AdjustmentLineData $data, array $lotRows): void
    {
        $lotIds = $this->syncLineLots($line, $data, $lotRows);

        $this->syncLineSerials($line, $data, $lotIds);
    }

    /**
     * @param  array<string, array<string, mixed>>  $lotRows
     * @return array<string, string> Id del lote del maestro → id de la fila.
     */
    private function syncLineLots(AdjustmentLine $line, AdjustmentLineData $data, array $lotRows): array
    {
        $existing = AdjustmentLineLot::query()
            ->where('adjustment_line_id', $line->id)
            ->get()
            ->keyBy('id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];
        $byLot = [];

        foreach ($data->lots as $lot) {
            $current = $lot->id !== null ? $existing->get($lot->id) : null;
            $row = $lotRows[$lot->lotId] ?? null;

            $attributes = [
                'company_id' => $line->company_id,
                'lot_id' => $lot->lotId,
                'counted_quantity' => $lot->countedQuantity,
                'system_quantity' => $row['system'] ?? 0.0,
                'difference_quantity' => $row['difference'] ?? 0.0,
                'base_quantity' => $row['base_quantity'] ?? 0.0,
                'movement_type' => $row['movement_type'] ?? AdjustmentLine::MOVEMENT_IN,
                'unit_cost' => $row['unit_cost'] ?? 0.0,
                'total_cost' => $row['total_cost'] ?? 0.0,
                'status' => $lot->status,
                'notes' => $lot->notes,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;
                $byLot[$lot->lotId] = $current->id;

                continue;
            }

            $persisted = AdjustmentLineLot::create([
                ...$attributes,
                'adjustment_line_id' => $line->id,
                'line_number' => ++$nextNumber,
            ]);

            $keep[] = $persisted->id;
            $byLot[$lot->lotId] = $persisted->id;
        }

        AdjustmentLineLot::query()
            ->where('adjustment_line_id', $line->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);

        return $byLot;
    }

    /**
     * @param  array<string, string>  $lotIds  Id del lote del maestro → id de la fila.
     */
    private function syncLineSerials(AdjustmentLine $line, AdjustmentLineData $data, array $lotIds): void
    {
        $existing = AdjustmentLineSerial::query()
            ->where('adjustment_line_id', $line->id)
            ->get()
            ->keyBy('id');

        $nextNumber = (int) $existing->max('line_number');
        $keep = [];

        foreach ($data->serials as $serial) {
            $current = $serial->id !== null ? $existing->get($serial->id) : null;

            $attributes = [
                'company_id' => $line->company_id,
                'adjustment_line_lot_id' => $serial->lotId !== null
                    ? ($lotIds[$serial->lotId] ?? null)
                    : null,
                'serial_id' => $serial->serialId,
                'status' => $serial->status,
            ];

            if ($current !== null) {
                $current->update($attributes);
                $keep[] = $current->id;

                continue;
            }

            $keep[] = AdjustmentLineSerial::create([
                ...$attributes,
                'adjustment_line_id' => $line->id,
                'line_number' => ++$nextNumber,
            ])->id;
        }

        AdjustmentLineSerial::query()
            ->where('adjustment_line_id', $line->id)
            ->when($keep !== [], fn ($q) => $q->whereNotIn('id', $keep))
            ->update(['status' => 'inactive']);
    }

    /**
     * Totales de la cabecera. Suman **solo** las líneas activas, y separan los
     * aumentos de las disminuciones: el impacto en el valor del inventario es
     * la resta de los dos, que es lo que decide si el ajuste necesita una
     * segunda firma. Una línea con lotes suma por lote, porque cada uno puede
     * ir en una dirección distinta.
     *
     * @param  array<int, AdjustmentLineData>  $lines
     * @param  array<int, array{base_quantity: float, movement_type: string, total_cost: float, lots: array<string, array{base_quantity: float, movement_type: string, total_cost: float}>}>  $rows
     * @return array<string, float>
     */
    private function totals(array $lines, array $rows): array
    {
        $quantityIn = 0.0;
        $quantityOut = 0.0;
        $costIn = 0.0;
        $costOut = 0.0;

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active') {
                continue;
            }

            $movements = $rows[$index]['lots'] !== []
                ? array_values($rows[$index]['lots'])
                : [$rows[$index]];

            foreach ($movements as $movement) {
                $quantity = abs($movement['base_quantity']);
                $cost = $movement['total_cost'];

                if ($movement['movement_type'] === AdjustmentLine::MOVEMENT_IN) {
                    $quantityIn += $quantity;
                    $costIn += $cost;

                    continue;
                }

                $quantityOut += $quantity;
                $costOut += $cost;
            }
        }

        return [
            'total_quantity_in' => round($quantityIn, 4),
            'total_quantity_out' => round($quantityOut, 4),
            'total_cost_in' => round($costIn, 2),
            'total_cost_out' => round($costOut, 2),
            'net_cost' => round($costIn - $costOut, 2),
        ];
    }

    /**
     * Generate the next sequential per-company code (AJU000001, AJU000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Adjustment::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Adjustment::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Adjustment::CODE_PREFIX))) + 1
            : 1;

        return Adjustment::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
