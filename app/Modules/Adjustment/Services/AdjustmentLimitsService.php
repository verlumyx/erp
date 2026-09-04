<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Commands\AdjustmentLineData;
use App\Modules\Adjustment\Commands\AdjustmentLineLotData;
use App\Modules\Adjustment\Commands\AdjustmentLineSerialData;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Item\Models\Item;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use App\Modules\ItemLot\Models\ItemLot;
use App\Modules\ItemSerial\Models\ItemSerial;
use Illuminate\Validation\ValidationException;

/**
 * Lo que el ajuste no puede comprobar sin haber resuelto la existencia ni leído
 * el maestro de artículos: que la dirección declarada sea la que las líneas
 * realmente producen, que el lote y la serie correspondan a artículos que los
 * llevan, y que una revaluación tenga un costo nuevo y algo que revaluar.
 *
 * Vive aparte de los servicios de acción porque Crear y Actualizar la necesitan
 * igual: el ajuste se edita en borrador y cada guardado vuelve a comprobar lo
 * mismo.
 */
class AdjustmentLimitsService
{
    public function __construct(
        private readonly ItemRepositoryInterface $items,
    ) {}

    /**
     * @param  array<int, AdjustmentLineData>  $lines
     * @param  array<int, array{factor: float, system: float, base_system: float, average: float}>  $stock
     *
     * @throws ValidationException
     */
    public function guard(
        string $type,
        string $direction,
        ?string $companyId,
        array $lines,
        array $stock,
    ): void {
        $items = $this->itemsOf($companyId, $lines);

        $this->guardTraceability($lines, $items, $stock);

        if ($type === Adjustment::REVALUATION_TYPE) {
            $this->guardRevaluation($lines, $stock);

            return;
        }

        $this->guardDirection($direction, $lines, $stock);
    }

    /**
     * La dirección declarada es una promesa sobre lo que el ajuste hace: un
     * ajuste de solo aumentos no puede esconder un faltante, y al revés. El
     * `mixed` no promete nada, así que no comprueba nada.
     *
     * @param  array<int, AdjustmentLineData>  $lines
     * @param  array<int, array{system: float}>  $stock
     *
     * @throws ValidationException
     */
    private function guardDirection(string $direction, array $lines, array $stock): void
    {
        if ($direction === 'mixed') {
            return;
        }

        $errors = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active') {
                continue;
            }

            $difference = round($line->countedQuantity - round($stock[$index]['system'] ?? 0.0, 4), 4);

            if ($direction === 'in' && $difference < 0.0) {
                $errors["lines.{$index}.counted_quantity"] = 'Este ajuste es solo de aumentos: esa línea deja un faltante.';
            }

            if ($direction === 'out' && $difference > 0.0) {
                $errors["lines.{$index}.counted_quantity"] = 'Este ajuste es solo de disminuciones: esa línea deja un sobrante.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Una revaluación no cuenta: reexpresa lo que ya está. Exige un costo nuevo
     * y existencia sobre la que aplicarlo, y no admite que lo contado difiera
     * de lo que dice el sistema —eso sería otro tipo de ajuste—.
     *
     * @param  array<int, AdjustmentLineData>  $lines
     * @param  array<int, array{system: float, base_system: float}>  $stock
     *
     * @throws ValidationException
     */
    private function guardRevaluation(array $lines, array $stock): void
    {
        $errors = [];

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active') {
                continue;
            }

            $system = round($stock[$index]['system'] ?? 0.0, 4);

            if (round($line->countedQuantity, 4) !== $system) {
                $errors["lines.{$index}.counted_quantity"] = 'Una revaluación no mueve cantidad: lo contado tiene que ser lo que dice el sistema.';
            }

            if (($line->unitCost ?? 0.0) <= 0.0) {
                $errors["lines.{$index}.unit_cost"] = 'Indica el costo nuevo con el que se revalúa la línea.';
            }

            if (round($stock[$index]['base_system'] ?? 0.0, 4) <= 0.0) {
                $errors["lines.{$index}.item_id"] = 'No hay existencia de ese artículo en la bodega: no hay nada que revaluar.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Lo que la trazabilidad exige del maestro de artículos: que el lote y la
     * serie sean de ese artículo y que solo se pidan a quien los lleva.
     *
     * De un artículo serializado se espera además que las series nombren
     * unidades enteras: o las que se contaron, o las que faltan. Por eso su
     * número tiene que ser lo contado o la diferencia, ambos en unidad base.
     *
     * @param  array<int, AdjustmentLineData>  $lines
     * @param  array<string, Item>  $items
     * @param  array<int, array{factor: float, system: float}>  $stock
     *
     * @throws ValidationException
     */
    private function guardTraceability(array $lines, array $items, array $stock): void
    {
        $errors = [];

        $lotItems = ItemLot::query()
            ->whereIn('id', $this->lotIdsOf($lines))
            ->pluck('item_id', 'id')
            ->all();

        $serialItems = ItemSerial::query()
            ->whereIn('id', $this->serialIdsOf($lines))
            ->pluck('item_id', 'id')
            ->all();

        foreach ($lines as $index => $line) {
            if ($line->status !== 'active') {
                continue;
            }

            $item = $items[$line->itemId] ?? null;

            if (! $item instanceof Item) {
                continue;
            }

            $lots = $line->activeLots();
            $serials = $line->activeSerials();

            if ($lots !== [] && ! $item->movesStock()) {
                $errors["lines.{$index}.lots"] = 'Ese artículo no se controla por lote.';
            }

            foreach ($lots as $lot) {
                if (($lotItems[$lot->lotId] ?? null) !== $line->itemId) {
                    $errors["lines.{$index}.lots"] = 'Alguno de los lotes no es de ese artículo.';

                    break;
                }
            }

            if ($item->type !== ItemSerial::TRACKABLE_ITEM_TYPE) {
                if ($serials !== []) {
                    $errors["lines.{$index}.serials"] = 'Ese artículo no se controla por serie.';
                }

                continue;
            }

            foreach ($serials as $serial) {
                if (($serialItems[$serial->serialId] ?? null) !== $line->itemId) {
                    $errors["lines.{$index}.serials"] = 'Alguna de las series no es de ese artículo.';

                    break;
                }
            }

            if ($serials === []) {
                continue;
            }

            $factor = $stock[$index]['factor'] ?? 1.0;
            $counted = round($line->countedQuantity * $factor, 4);
            $difference = round(($line->countedQuantity - round($stock[$index]['system'] ?? 0.0, 4)) * $factor, 4);

            if (! $this->namesWholeUnits(count($serials), $counted, $difference)) {
                $errors["lines.{$index}.serials"] = 'Una serie es una unidad: nombra las que se contaron o las que faltan.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * ¿El número de series nombradas cuadra con lo que la línea dice? Vale que
     * sean las contadas —el conteo identifica lo que encontró— o que sean las
     * que faltan —el conteo identifica lo que se fue—.
     */
    private function namesWholeUnits(int $serials, float $counted, float $difference): bool
    {
        foreach ([$counted, abs($difference)] as $units) {
            if ($units == (float) (int) $units && $serials === (int) $units) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, AdjustmentLineData>  $lines
     * @return array<int, string>
     */
    private function lotIdsOf(array $lines): array
    {
        $ids = [];

        foreach ($lines as $line) {
            foreach ($line->activeLots() as $lot) {
                /** @var AdjustmentLineLotData $lot */
                $ids[$lot->lotId] = $lot->lotId;
            }
        }

        return array_values($ids);
    }

    /**
     * @param  array<int, AdjustmentLineData>  $lines
     * @return array<int, string>
     */
    private function serialIdsOf(array $lines): array
    {
        $ids = [];

        foreach ($lines as $line) {
            foreach ($line->activeSerials() as $serial) {
                /** @var AdjustmentLineSerialData $serial */
                $ids[$serial->serialId] = $serial->serialId;
            }
        }

        return array_values($ids);
    }

    /**
     * Artículos de las líneas, indexados por id y resueltos a través del
     * repositorio de artículos: este módulo nunca consulta las tablas del
     * módulo de inventario directamente.
     *
     * @param  array<int, AdjustmentLineData>  $lines
     * @return array<string, Item>
     */
    private function itemsOf(?string $companyId, array $lines): array
    {
        $items = [];

        foreach (array_unique(array_map(static fn (AdjustmentLineData $line): string => $line->itemId, $lines)) as $itemId) {
            $item = $this->items->findById($itemId, $companyId);

            if ($item instanceof Item) {
                $items[$itemId] = $item;
            }
        }

        return $items;
    }
}
