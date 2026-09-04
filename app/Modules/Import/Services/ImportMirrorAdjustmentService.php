<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Adjustment\Commands\AdjustmentLineData;
use App\Modules\Adjustment\Commands\AdjustmentLineLotData;
use App\Modules\Adjustment\Commands\CreateAdjustmentCommand;
use App\Modules\Adjustment\Commands\UpdateStatusAdjustmentCommand;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use App\Modules\Adjustment\Services\AdjustmentCreateService;
use App\Modules\Adjustment\Services\AdjustmentStockService;
use App\Modules\Adjustment\Services\AdjustmentUpdateStatusService;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Models\ImportLine;
use App\Modules\Import\Models\ImportLineLot;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * El ajuste espejo del expediente: el documento que sí reexpresa el costo.
 *
 * El expediente no toca el kardex —solo Ajuste, Entrada y Despacho lo hacen, y
 * esa regla no tiene excepciones—. Confirmarlo escribe lo que hay que
 * revalorizar: un ajuste `AJU` de tipo `revaluation` en borrador, en la bodega
 * del expediente y colgado de él. Ahí se acaba su papel; cuando alguien firma
 * ese ajuste, el inventario cambia de valor y el expediente queda cerrado.
 *
 * Al ajuste solo se le pasa una cosa por línea: el costo nuevo. Él ya sabe
 * hacer el resto —en `revaluation` no mueve cantidad y mide el impacto contra
 * la existencia viva del momento en que se confirma—, y por eso lo que el
 * expediente estimó puede no ser lo que acabe capitalizando: manda lo que hay
 * cuando el asiento se escribe.
 *
 * Varias líneas del mismo artículo y ubicación colapsan en **una** línea del
 * ajuste, y sus lotes en las filas de lote de esa línea, porque el ajuste
 * revaloriza una existencia y no una recepción.
 */
class ImportMirrorAdjustmentService
{
    public function __construct(
        private readonly ImportRepositoryInterface $imports,
        private readonly AdjustmentRepositoryInterface $adjustments,
        private readonly AdjustmentCreateService $createAdjustment,
        private readonly AdjustmentUpdateStatusService $updateAdjustmentStatus,
        private readonly AdjustmentStockService $stock,
    ) {}

    /**
     * Crea el ajuste que reexpresará el costo.
     *
     * Devuelve `null` cuando no hay nada que revalorizar: un expediente sin
     * gasto capitalizable, o uno que ya tiene su ajuste.
     */
    public function create(Import $import): ?Adjustment
    {
        if ($this->liveAdjustment($import) instanceof Adjustment) {
            return null;
        }

        $lines = $this->revaluationLines($import);

        if ($lines === []) {
            return null;
        }

        $id = (string) Str::uuid7();

        $this->createAdjustment->execute(new CreateAdjustmentCommand(
            id: $id,
            companyId: (string) $import->company_id,
            warehouseId: (string) $import->warehouse_id,
            adjustmentDate: $import->import_date?->toDateString() ?? now()->toDateString(),
            reason: "Revaluación por la importación {$import->code}.",
            createdBy: (string) $import->created_by,
            lines: $lines,
            type: Adjustment::REVALUATION_TYPE,
            direction: 'mixed',
            notes: "Generado al confirmar el expediente de importación {$import->code}.",
        ));

        $this->imports->writeAdjustment($import, $id);

        return $this->adjustments->findOrFail($id, $import->company_id);
    }

    /**
     * Anula el ajuste en borrador del expediente, si lo hay. Lo llama el
     * expediente al anularse: lo que ya no se reparte tampoco se revaloriza.
     */
    public function cancel(Import $import): void
    {
        $adjustment = $this->liveAdjustment($import);

        if (! $adjustment instanceof Adjustment
            || in_array($adjustment->status, Adjustment::POSTED_STATUSES, true)
        ) {
            return;
        }

        $this->updateAdjustmentStatus->execute(
            $adjustment->id,
            new UpdateStatusAdjustmentCommand(
                status: 'cancelled',
                cancellationReason: "Anulación del expediente de importación {$import->code}.",
            ),
            $import->company_id,
        );

        $this->imports->writeAdjustment($import, null);
    }

    /**
     * Un expediente cuyo ajuste ya cambió el valor del inventario no se anula
     * por las buenas: primero hay que anular ese ajuste, que es el que sabe
     * deshacer sus propios asientos en el kardex.
     *
     * @throws ValidationException
     */
    public function guardCancellable(Import $import): void
    {
        $adjustment = $this->liveAdjustment($import);

        if (! $adjustment instanceof Adjustment
            || ! in_array($adjustment->status, Adjustment::POSTED_STATUSES, true)
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "El expediente ya tiene el ajuste {$adjustment->code} aplicado: anúlalo primero.",
        ]);
    }

    /**
     * El ajuste del expediente que todavía cuenta. Uno anulado no estorba: el
     * expediente puede volver a generar el suyo.
     */
    public function liveAdjustment(Import $import): ?Adjustment
    {
        if (blank($import->adjustment_id)) {
            return null;
        }

        $adjustment = $this->adjustments->findById($import->adjustment_id, $import->company_id);

        if (! $adjustment instanceof Adjustment || $adjustment->status === 'cancelled') {
            return null;
        }

        return $adjustment;
    }

    /**
     * Las líneas del ajuste: una por cada par `(artículo, ubicación)` con gasto
     * capitalizable, con el costo nuevo que el ajuste va a escribir.
     *
     * Lo contado es la existencia que el sistema tiene ahora mismo, porque una
     * revaluación no mueve cantidad: lo que cambia es lo que vale la que ya
     * está. El grupo sin existencia viva se queda fuera —no hay nada que
     * revaluar— y su gasto se queda como varianza del expediente.
     *
     * @return array<int, AdjustmentLineData>
     */
    private function revaluationLines(Import $import): array
    {
        $groups = $this->groupsOf($import);

        if ($groups === []) {
            return [];
        }

        $balances = $this->stock->resolveForKeys(
            $import->company_id,
            (string) $import->warehouse_id,
            array_map(
                static fn (array $group): array => $group['key'],
                array_values($groups),
            ),
        );

        $lines = [];

        foreach (array_values($groups) as $index => $group) {
            $balance = $balances[$index] ?? ['system' => 0.0, 'base_system' => 0.0, 'average' => 0.0];

            if (round((float) $balance['base_system'], 4) <= 0.0) {
                continue;
            }

            $lots = $this->lotsOf($import, $group);

            /**
             * Un grupo con lotes cuenta esos lotes y no todo lo que haya en la
             * ubicación: si ninguno tiene saldo vivo, no hay nada que revaluar.
             */
            if ($group['lots'] !== [] && $lots === []) {
                continue;
            }

            $lines[] = new AdjustmentLineData(
                id: null,
                itemId: $group['key']['item_id'],
                measurementUnitId: $group['key']['measurement_unit_id'],
                countedQuantity: $lots === []
                    ? round((float) $balance['system'], 4)
                    : round(array_sum(array_map(
                        static fn (AdjustmentLineLotData $lot): float => $lot->countedQuantity,
                        $lots,
                    )), 4),
                locationId: $group['key']['location_id'],
                lots: $lots,
                unitCost: round((float) $balance['average'] + $this->deltaOf($group), 6),
                reason: "Costo puesto en bodega del expediente {$import->code}.",
            );
        }

        return $lines;
    }

    /**
     * Los ítems del expediente agrupados por la existencia que revalorizan.
     *
     * @return array<string, array{key: array{item_id: string, measurement_unit_id: string, location_id: ?string, lot_id: null}, allocated: float, base: float, lots: array<int, ImportLineLot>}>
     */
    private function groupsOf(Import $import): array
    {
        $groups = [];

        foreach ($this->imports->activeLines($import) as $line) {
            /** @var ImportLine $line */
            if (abs((float) $line->capitalized_amount) <= 0.0) {
                continue;
            }

            $signature = implode('|', [$line->item_id, $line->location_id ?? '']);

            $groups[$signature] ??= [
                'key' => [
                    'item_id' => (string) $line->item_id,
                    'measurement_unit_id' => (string) $line->measurement_unit_id,
                    'location_id' => $line->location_id,
                    'lot_id' => null,
                ],
                'allocated' => 0.0,
                'base' => 0.0,
                'lots' => [],
            ];

            $groups[$signature]['allocated'] += (float) $line->allocated_amount;
            $groups[$signature]['base'] += (float) $line->base_quantity;

            foreach ($line->lots as $lot) {
                /** @var ImportLineLot $lot */
                if ($lot->status === 'active') {
                    $groups[$signature]['lots'][] = $lot;
                }
            }
        }

        return $groups;
    }

    /**
     * El incremento por unidad del grupo: la suma de los gastos asignados
     * dividida entre la suma de las cantidades que entraron.
     *
     * @param  array{allocated: float, base: float}  $group
     */
    private function deltaOf(array $group): float
    {
        return $group['base'] > 0.0
            ? round($group['allocated'] / $group['base'], 6)
            : 0.0;
    }

    /**
     * Las filas de lote de la línea del ajuste: una por cada caja del grupo con
     * saldo vivo, con **su** costo nuevo. Dos lotes de la misma línea pueden
     * tener distinto saldo, y por tanto distinto incremento por unidad.
     *
     * @param  array{key: array<string, mixed>, lots: array<int, ImportLineLot>}  $group
     * @return array<int, AdjustmentLineLotData>
     */
    private function lotsOf(Import $import, array $group): array
    {
        if ($group['lots'] === []) {
            return [];
        }

        /** El mismo lote puede venir en dos recepciones: lo revaloriza una vez. */
        $pooled = [];

        foreach ($group['lots'] as $lot) {
            $pooled[$lot->lot_id] ??= ['allocated' => 0.0, 'base' => 0.0];
            $pooled[$lot->lot_id]['allocated'] += (float) $lot->allocated_amount;
            $pooled[$lot->lot_id]['base'] += (float) $lot->base_quantity;
        }

        $balances = $this->stock->resolveForKeys(
            $import->company_id,
            (string) $import->warehouse_id,
            array_map(
                static fn (string $lotId): array => [...$group['key'], 'lot_id' => $lotId],
                array_keys($pooled),
            ),
        );

        $rows = [];

        foreach (array_keys($pooled) as $index => $lotId) {
            $balance = $balances[$index] ?? ['system' => 0.0, 'base_system' => 0.0, 'average' => 0.0];

            if (round((float) $balance['base_system'], 4) <= 0.0) {
                continue;
            }

            $rows[] = new AdjustmentLineLotData(
                id: null,
                lotId: (string) $lotId,
                countedQuantity: round((float) $balance['system'], 4),
                unitCost: round((float) $balance['average'] + $this->deltaOf($pooled[$lotId]), 6),
            );
        }

        return $rows;
    }
}
