<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Commands\WriteAdjustmentLineCostCommand;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Models\AdjustmentLine;
use App\Modules\Adjustment\Models\AdjustmentLineLot;
use App\Modules\Adjustment\Models\AdjustmentLineSerial;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;
use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\Item\Commands\ApplyItemAverageCostCommand;
use App\Modules\Item\Models\ItemUnit;
use App\Modules\Item\Services\ItemApplyAverageCostService;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que el ajuste deja de ser un papel y la existencia cambia.
 *
 * Confirmarlo escribe en el kardex un movimiento por cada línea que difiere:
 * el sobrante carga, el faltante descarga. Anularlo emite la contrapartida de
 * cada uno. Mientras está en borrador —o esperando aprobación— sus líneas ya
 * están escritas, pero ninguna existencia se ha movido.
 *
 * El sobrante entra al promedio vigente de lo que se está contando: aparecer no
 * es comprar, así que no cambia lo que la mercancía vale. El faltante sale sin
 * costo impuesto y el kardex lo valora al promedio, que es lo mismo.
 *
 * Una revaluación es el caso aparte: no mueve cantidad, cambia lo que vale la
 * que ya está. El kardex no sabe escribir un movimiento de cantidad cero, así
 * que se expresa como lo que de verdad es —sacar lo que hay al costo viejo y
 * volver a meterlo al nuevo—, y por eso una anulación deshace los movimientos
 * en orden inverso: es la única forma de devolver el promedio a donde estaba.
 *
 * La trazabilidad parte el asiento: una línea que cuenta lotes escribe **un
 * movimiento por lote**, cada uno con su propia diferencia y su propio costo.
 * No es un detalle estético —la existencia se guarda por lote—: sin partirlo,
 * la corrección caería en el saldo equivocado.
 */
class AdjustmentPostingService
{
    public function __construct(
        private readonly AdjustmentRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly ItemApplyAverageCostService $applyAverageCost,
    ) {}

    /** Aplica el ajuste: cada línea mueve su diferencia en su ubicación. */
    public function post(Adjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment): void {
            $lines = $this->repository->activeLines($adjustment);

            foreach ($lines as $line) {
                $applied = [];

                foreach ($this->targetsOf($line) as $target) {
                    $result = $adjustment->isRevaluation()
                        ? $this->revalue($adjustment, $line, $target)
                        : $this->registerDifference($adjustment, $line, $target);

                    if ($result !== null) {
                        $applied[] = $result;
                    }
                }

                $this->writeLineTotals($line, $applied);
            }

            $this->repository->refreshTotals($adjustment);
            $this->refreshAverageCosts($adjustment, $lines);
        });
    }

    /**
     * En cuántos asientos se parte una línea, y con qué lote y qué serie va
     * cada uno.
     *
     * Un lote es una existencia aparte —el kardex la guarda por lote—, así que
     * cada uno tiene su propio asiento con su propia diferencia. Sin lotes, la
     * línea entera es un solo asiento.
     *
     * @return array<int, array{lot: ?AdjustmentLineLot}>
     */
    private function targetsOf(AdjustmentLine $line): array
    {
        $lots = $line->lots->where('status', 'active');

        if ($lots->isEmpty()) {
            return [['lot' => null]];
        }

        return $lots->map(static fn (AdjustmentLineLot $lot): array => ['lot' => $lot])
            ->values()
            ->all();
    }

    /**
     * La unidad concreta que el asiento nombra.
     *
     * Las series de una línea dicen qué unidades entran en el conteo, no cuáles
     * se mueven: contar cinco laptops y encontrar cuatro nombra las cinco, pero
     * el kardex solo saca una. Por eso el asiento se identifica con una serie
     * únicamente cuando no queda ambigüedad: mueve una unidad y hay una sola
     * serie a la que pueda referirse.
     */
    private function serialFor(AdjustmentLine $line, ?AdjustmentLineLot $lot, float $quantity): ?string
    {
        $serials = $line->serials->where('status', 'active');

        if ($lot !== null) {
            $serials = $serials->where('adjustment_line_lot_id', $lot->id);
        }

        if ($quantity != 1.0 || $serials->count() !== 1) {
            return null;
        }

        /** @var AdjustmentLineSerial $serial */
        $serial = $serials->first();

        return $serial->serial_id;
    }

    /**
     * Deja en la línea el resultado de sus asientos: con varios lotes el costo
     * es el promedio ponderado por cantidad y el valor la suma, porque la línea
     * tiene una sola columna de cada cosa.
     *
     * @param  array<int, array{quantity: float, unit_cost: float, total_cost: float, movement_type: string}>  $applied
     */
    private function writeLineTotals(AdjustmentLine $line, array $applied): void
    {
        if ($applied === []) {
            return;
        }

        $quantity = array_sum(array_column($applied, 'quantity'));

        $value = array_sum(array_map(
            static fn (array $entry): float => $entry['quantity'] * $entry['unit_cost'],
            $applied,
        ));

        $signedCost = array_sum(array_map(
            static fn (array $entry): float => $entry['movement_type'] === AdjustmentLine::MOVEMENT_IN
                ? $entry['total_cost']
                : -$entry['total_cost'],
            $applied,
        ));

        $this->repository->writeLineCost($line, new WriteAdjustmentLineCostCommand(
            unitCost: $quantity > 0.0 ? round($value / $quantity, 6) : 0.0,
            totalCost: round(array_sum(array_column($applied, 'total_cost')), 2),
            movementType: $signedCost < 0.0
                ? AdjustmentLine::MOVEMENT_OUT
                : AdjustmentLine::MOVEMENT_IN,
        ));
    }

    /**
     * Deshace el ajuste: cada movimiento del kardex recibe su contrapartida, del
     * último al primero. Ninguna fila se borra.
     *
     * El orden importa en una revaluación: deshacer primero la entrada al costo
     * nuevo y después la salida al viejo devuelve el promedio a donde estaba;
     * al revés lo dejaría a mitad de camino entre los dos.
     */
    public function reverse(Adjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment): void {
            foreach ($this->postedMovements($adjustment) as $movement) {
                $this->reversals->execute(new ReverseInventoryMovementCommand(
                    movementId: $movement->id,
                    notes: "Anulación del ajuste {$adjustment->code}.",
                    createdBy: $adjustment->created_by,
                ));
            }

            $this->refreshAverageCosts($adjustment, $this->repository->activeLines($adjustment));
        });
    }

    /**
     * El movimiento de una existencia contada, en unidad base: la línea entera
     * o uno de sus lotes.
     *
     * Lo que cuadró no llega al kardex: contar y encontrar lo que decía el
     * sistema no es un ajuste. Un artículo sin existencia —un servicio colado
     * en el conteo— tampoco: la línea vale para el acta, pero no hay saldo que
     * mover.
     *
     * @param  array{lot: ?AdjustmentLineLot}  $target
     * @return array{quantity: float, unit_cost: float, total_cost: float, movement_type: string}|null
     */
    private function registerDifference(Adjustment $adjustment, AdjustmentLine $line, array $target): ?array
    {
        $lot = $target['lot'];
        $quantity = $lot?->movedQuantity() ?? $line->movedQuantity();

        if ($quantity <= 0.0) {
            return null;
        }

        $type = $lot?->movement_type ?? $line->movement_type;
        $inbound = $type === AdjustmentLine::MOVEMENT_IN;

        $movement = $this->register(
            $adjustment,
            $line,
            $type,
            $quantity,
            /** El sobrante entra al promedio ya congelado; el faltante lo toma del kardex. */
            $inbound ? round((float) ($lot?->unit_cost ?? $line->unit_cost), 6) : null,
            $lot?->lot_id,
            $this->serialFor($line, $lot, $quantity),
        );

        if ($movement === null) {
            return null;
        }

        $applied = [
            'quantity' => $quantity,
            'unit_cost' => (float) $movement->unit_cost,
            'total_cost' => round($quantity * (float) $movement->unit_cost, 2),
            'movement_type' => $type,
        ];

        if ($lot !== null) {
            $this->repository->writeLotCost($lot, new WriteAdjustmentLineCostCommand(
                unitCost: $applied['unit_cost'],
                totalCost: $applied['total_cost'],
                movementType: $type,
            ));
        }

        return $applied;
    }

    /**
     * La revaluación de una existencia: sale toda al costo con el que está
     * valorada y vuelve a entrar al costo nuevo. Lo que queda escrito es el
     * impacto real —la cantidad por la diferencia de costo— y la dirección que
     * ese impacto tuvo.
     *
     * @param  array{lot: ?AdjustmentLineLot}  $target
     * @return array{quantity: float, unit_cost: float, total_cost: float, movement_type: string}|null
     */
    private function revalue(Adjustment $adjustment, AdjustmentLine $line, array $target): ?array
    {
        $lot = $target['lot'];
        $quantity = $this->baseSystemQuantity($line, $lot);

        if ($quantity <= 0.0) {
            return null;
        }

        $newCost = round((float) ($lot?->unit_cost ?? $line->unit_cost), 6);

        $out = $this->register($adjustment, $line, AdjustmentLine::MOVEMENT_OUT, $quantity, null, $lot?->lot_id, null);

        if ($out === null) {
            return null;
        }

        $this->register($adjustment, $line, AdjustmentLine::MOVEMENT_IN, $quantity, $newCost, $lot?->lot_id, null);

        $delta = round($newCost - (float) $out->unit_cost, 6);

        $applied = [
            'quantity' => $quantity,
            'unit_cost' => $newCost,
            'total_cost' => round(abs($quantity * $delta), 2),
            'movement_type' => $delta < 0.0
                ? AdjustmentLine::MOVEMENT_OUT
                : AdjustmentLine::MOVEMENT_IN,
        ];

        if ($lot !== null) {
            $this->repository->writeLotCost($lot, new WriteAdjustmentLineCostCommand(
                unitCost: $applied['unit_cost'],
                totalCost: $applied['total_cost'],
                movementType: $applied['movement_type'],
            ));
        }

        return $applied;
    }

    /**
     * Un asiento del kardex. Devuelve `null` cuando el artículo no lleva
     * existencia, que no es un error: la línea simplemente no tiene saldo que
     * mover.
     */
    private function register(
        Adjustment $adjustment,
        AdjustmentLine $line,
        string $type,
        float $quantity,
        ?float $unitCost,
        ?string $lotId,
        ?string $serialId,
    ): ?InventoryMovement {
        try {
            return $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $adjustment->company_id,
                itemId: $line->item_id,
                warehouseId: $adjustment->warehouse_id,
                locationId: $this->locationFor($adjustment, $line),
                type: $type,
                originType: Adjustment::MOVEMENT_ORIGIN_TYPE,
                originId: $adjustment->id,
                quantity: $quantity,
                unitCost: $unitCost,
                movementDate: $adjustment->adjustment_date?->toDateString(),
                originLineId: $line->id,
                lotId: $lotId,
                serialId: $serialId,
                notes: $line->reason ?? $adjustment->reason,
                createdBy: $adjustment->created_by,
            ));
        } catch (NonInventoriedItemException) {
            return null;
        } catch (InsufficientStockException) {
            throw ValidationException::withMessages([
                'status' => 'No hay existencia suficiente en la bodega para aplicar todas las líneas del ajuste.',
            ]);
        }
    }

    /**
     * Existencia del sistema llevada a la unidad base: la cantidad que una
     * revaluación reexpresa. No sale de `base_quantity` —ahí no hay nada, la
     * revaluación no mueve cantidad—, sino del factor del artículo. Con lotes
     * es la de cada lote: es su saldo el que se reexpresa.
     */
    private function baseSystemQuantity(AdjustmentLine $line, ?AdjustmentLineLot $lot = null): float
    {
        $factor = 1.0;

        foreach ($line->item?->units ?? [] as $unit) {
            /** @var ItemUnit $unit */
            if ($unit->measurement_unit_id === $line->measurement_unit_id) {
                $factor = (float) $unit->conversion_factor;

                break;
            }
        }

        return round((float) ($lot?->system_quantity ?? $line->system_quantity) * $factor, 4);
    }

    /**
     * Movimientos vivos que escribió este ajuste, del último al primero: el
     * kardex se lee del más reciente hacia atrás, que es justo el orden en el
     * que hay que deshacerlos. Las contrapartidas de una anulación anterior
     * quedan fuera: llevan `reversal_of_id`, y anularlas otra vez volvería a
     * aplicar el ajuste.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(Adjustment $adjustment): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => Adjustment::MOVEMENT_ORIGIN_TYPE,
                'origin_id' => $adjustment->id,
                'status' => 'active',
            ],
            limit: PHP_INT_MAX,
            companyId: $adjustment->company_id,
        ));

        return array_values(array_filter(
            $result['data'],
            static fn (InventoryMovement $movement): bool => $movement->reversal_of_id === null,
        ));
    }

    /**
     * Recalcula el costo promedio de los artículos que el ajuste movió. Va al
     * final y no dentro del bucle: dos líneas del mismo artículo dejan un solo
     * promedio, y el que vale es el de después de haberlas aplicado todas.
     *
     * @param  array<int, AdjustmentLine>  $lines
     */
    private function refreshAverageCosts(Adjustment $adjustment, array $lines): void
    {
        $itemIds = array_unique(array_map(
            static fn (AdjustmentLine $line): string => $line->item_id,
            $lines,
        ));

        foreach ($itemIds as $itemId) {
            $this->applyAverageCost->execute(new ApplyItemAverageCostCommand(
                companyId: $adjustment->company_id,
                itemId: $itemId,
            ));
        }
    }

    /**
     * Dónde se aplica físicamente el ajuste. La línea puede decirlo; si calla,
     * se usa la ubicación por defecto de la bodega, y si la bodega no tiene
     * ninguna el ajuste no se confirma: el kardex no mueve saldo sin sitio.
     */
    private function locationFor(Adjustment $adjustment, AdjustmentLine $line): string
    {
        if (filled($line->location_id)) {
            return $line->location_id;
        }

        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: [
                'warehouse_id' => $adjustment->warehouse_id,
                'is_default' => 'yes',
                'status' => 'active',
            ],
            limit: 1,
            companyId: $adjustment->company_id,
        ));

        $location = $result['data'][0] ?? null;

        if (! $location instanceof WarehouseLocation) {
            throw ValidationException::withMessages([
                'status' => 'La bodega no tiene ubicación por defecto: indícala en cada línea antes de confirmar.',
            ]);
        }

        return $location->id;
    }
}
