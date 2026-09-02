<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\WriteDispatchLineCostCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\Dispatch\Models\DispatchLineLot;
use App\Modules\Dispatch\Models\DispatchLineSerial;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\SalesOrder\Commands\ApplySalesOrderDispatchCommand;
use App\Modules\SalesOrder\Exceptions\SalesOrderOverDispatchedException;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Services\SalesOrderApplyDispatchService;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;
use App\Modules\Transfer\Services\TransferApplyProgressService;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que el despacho deja de ser un papel y la mercancía sale de la
 * bodega.
 *
 * Confirmarlo escribe una salida en el kardex por cada línea y apunta lo
 * despachado en el pedido de origen, que además libera su reserva: lo que ya no
 * está en la bodega no puede seguir comprometido. Anularlo emite la
 * contrapartida y devuelve ese cupo.
 *
 * La salida **no lleva costo impuesto**: la valora el kardex con el promedio
 * vigente, que es exactamente lo que significa «costo al momento de la salida».
 * Ese costo se copia después a la línea, y con él se recalcula el costo total
 * de la carga.
 */
class DispatchPostingService
{
    public function __construct(
        private readonly DispatchRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly SalesOrderApplyDispatchService $applyToOrderLine,
        private readonly TransferApplyProgressService $transfers,
    ) {}

    /**
     * Saca la mercancía. Cada línea sale de su ubicación al promedio vigente.
     */
    public function post(Dispatch $dispatch): void
    {
        DB::transaction(function () use ($dispatch): void {
            $lines = $this->repository->activeLines($dispatch);

            $this->guardSerials($lines);

            foreach ($lines as $line) {
                $this->registerExit($dispatch, $line);
                $this->moveOrderLine($dispatch, $line, round((float) $line->quantity, 4));
            }

            $this->markTransferShipped($dispatch, true);

            $this->repository->refreshTotals($dispatch);
        });
    }

    /**
     * Deshace la salida: cada movimiento del kardex recibe su contrapartida y
     * el pedido recupera lo despachado. Ninguna fila se borra.
     *
     * Se revierte lo que quedó aplicado, no lo que salió: si el viaje ya se
     * registró, parte de la mercancía volvió sola y el pedido ya lo sabe.
     */
    public function reverse(Dispatch $dispatch): void
    {
        DB::transaction(function () use ($dispatch): void {
            foreach ($this->postedMovements($dispatch) as $movement) {
                $this->reversals->execute(new ReverseInventoryMovementCommand(
                    movementId: $movement->id,
                    notes: "Anulación del despacho {$dispatch->code}.",
                    createdBy: $dispatch->created_by,
                ));
            }

            foreach ($this->repository->activeLines($dispatch) as $line) {
                $applied = $dispatch->isDeliverySettled()
                    ? round((float) $line->delivered_quantity, 4)
                    : round((float) $line->quantity, 4);

                $this->moveOrderLine($dispatch, $line, -$applied);
            }

            $this->markTransferShipped($dispatch, false);
        });
    }

    /**
     * Le dice al traslado si su mercancía está en camino. Solo tiene sentido
     * cuando detrás del despacho hay uno: un despacho de venta no avisa a nadie
     * más que a su pedido.
     */
    private function markTransferShipped(Dispatch $dispatch, bool $shipped): void
    {
        if (! $dispatch->servesTransfer() || ! $dispatch->sourceable instanceof Transfer) {
            return;
        }

        $this->transfers->markShipped($dispatch->sourceable, $dispatch->created_by, $shipped);
    }

    /**
     * Un artículo serializado no sale sin sus series: el kardex identifica cada
     * unidad por la suya, así que hacen falta tantas como unidades base salen.
     *
     * La pantalla ya lo exige al guardar, pero un despacho puede nacer sin
     * ellas —el que genera el pedido de venta al aprobarse no puede
     * inventarlas—, así que el número se pide aquí, que es cuando la mercancía
     * de verdad se mueve.
     *
     * @param  array<int, DispatchLine>  $lines
     *
     * @throws ValidationException
     */
    private function guardSerials(array $lines): void
    {
        $errors = [];

        foreach ($lines as $line) {
            if ($line->item?->type !== ItemSerial::TRACKABLE_ITEM_TYPE) {
                continue;
            }

            $expected = round((float) $line->base_quantity, 4);

            if ($line->serials->where('status', 'active')->count() === (int) $expected && $expected == (float) (int) $expected) {
                continue;
            }

            $errors['status'] = "La línea {$line->line_number} es de un artículo con serie: indica una serie por cada unidad que sale ({$expected}).";
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * La salida de una línea, en unidad base.
     *
     * La trazabilidad decide en cuántos asientos se parte: un artículo
     * serializado sale unidad por unidad —cada serie es un asiento de una—, uno
     * con lote sale un asiento por lote, y uno sin nada de eso en un solo
     * asiento. Sumados, los asientos son exactamente la cantidad de la línea.
     *
     * Un artículo sin existencia —un servicio colado en el despacho— no llega
     * al kardex: la línea vale para la guía, pero no hay saldo que mover.
     */
    private function registerExit(Dispatch $dispatch, DispatchLine $line): void
    {
        $movements = [];

        try {
            foreach ($this->exitPlan($line) as $exit) {
                $movements[] = $this->movements->execute(new RegisterInventoryMovementCommand(
                    companyId: (string) $dispatch->company_id,
                    itemId: $line->item_id,
                    warehouseId: $dispatch->warehouse_id,
                    locationId: $this->locationFor($dispatch, $line),
                    /**
                     * Un despacho que sirve un traslado no vende nada: la
                     * mercancía sigue siendo de la empresa, solo cambia de
                     * bodega. El kardex lo dice con el tipo, no con el importe.
                     */
                    type: $dispatch->servesTransfer() ? 'transfer_out' : 'out',
                    originType: Dispatch::MOVEMENT_ORIGIN_TYPE,
                    originId: $dispatch->id,
                    quantity: $exit['quantity'],
                    /** Sin costo impuesto: la salida se valora al promedio vigente. */
                    unitCost: null,
                    movementDate: $dispatch->dispatch_date?->toDateString(),
                    originLineId: $line->id,
                    lotId: $exit['lotId'],
                    serialId: $exit['serialId'],
                    notes: $line->notes,
                    createdBy: $dispatch->created_by,
                ));
            }
        } catch (NonInventoriedItemException) {
            return;
        } catch (InsufficientStockException) {
            throw ValidationException::withMessages([
                'status' => 'No hay existencia suficiente en la bodega para despachar todas las líneas.',
            ]);
        }

        if ($movements === []) {
            return;
        }

        /**
         * El costo con el que salió de verdad se guarda en la línea. Con varios
         * asientos es el promedio ponderado por cantidad: la línea tiene una
         * sola columna de costo y cada lote pudo salir al suyo.
         */
        $quantity = array_sum(array_map(
            static fn (InventoryMovement $movement): float => (float) $movement->quantity,
            $movements,
        ));

        $value = array_sum(array_map(
            static fn (InventoryMovement $movement): float => (float) $movement->quantity * (float) $movement->unit_cost,
            $movements,
        ));

        $unitCost = $quantity > 0.0 ? round($value / $quantity, 6) : 0.0;

        $this->repository->writeLineCost($line, new WriteDispatchLineCostCommand(unitCost: $unitCost));

        /**
         * El costo con el que la mercancía salió viaja con ella: la línea del
         * traslado lo congela para que el destino la reciba al costo del
         * origen y trasladar no invente ni destruya valor.
         */
        if ($dispatch->servesTransfer() && $line->sourceable instanceof TransferLine) {
            $this->transfers->writeLineCost($line->sourceable, $unitCost);
        }
    }

    /**
     * En cuántos asientos se parte la salida de la línea, y con qué lote y qué
     * serie va cada uno.
     *
     * Cuando la línea lleva series, cada una es un asiento de una unidad base.
     * Cuando solo lleva lotes, cada lote sale por lo suyo. Sin trazabilidad, un
     * único asiento por la cantidad completa.
     *
     * @return array<int, array{lotId: ?string, serialId: ?string, quantity: float}>
     */
    private function exitPlan(DispatchLine $line): array
    {
        $serials = $line->serials->where('status', 'active');

        if ($serials->isNotEmpty()) {
            return $serials
                ->map(static fn (DispatchLineSerial $serial): array => [
                    'lotId' => $serial->dispatchLineLot?->lot_id,
                    'serialId' => $serial->serial_id,
                    'quantity' => 1.0,
                ])
                ->values()
                ->all();
        }

        $lots = $line->lots->where('status', 'active');

        if ($lots->isNotEmpty()) {
            return $lots
                ->map(static fn (DispatchLineLot $lot): array => [
                    'lotId' => $lot->lot_id,
                    'serialId' => null,
                    'quantity' => round((float) $lot->base_quantity, 4),
                ])
                ->values()
                ->all();
        }

        return [[
            'lotId' => null,
            'serialId' => null,
            'quantity' => round((float) $line->base_quantity, 4),
        ]];
    }

    /**
     * Movimientos vivos que escribió este despacho. Las contrapartidas de una
     * anulación anterior quedan fuera: llevan `reversal_of_id`, y anularlas otra
     * vez volvería a sacar la mercancía.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(Dispatch $dispatch): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => Dispatch::MOVEMENT_ORIGIN_TYPE,
                'origin_id' => $dispatch->id,
                'status' => 'active',
            ],
            limit: PHP_INT_MAX,
            companyId: $dispatch->company_id,
        ));

        return array_values(array_filter(
            $result['data'],
            static fn (InventoryMovement $movement): bool => $movement->reversal_of_id === null,
        ));
    }

    /**
     * Apunta —o libera— lo despachado en la línea del pedido de origen. Un
     * despacho directo no tiene dónde apuntarlo.
     */
    public function moveOrderLine(Dispatch $dispatch, DispatchLine $line, float $delta): void
    {
        if (blank($line->sourceable_id) || $line->sourceable_type !== SalesOrderLine::MORPH_ALIAS || $delta === 0.0) {
            return;
        }

        try {
            $this->applyToOrderLine->execute(new ApplySalesOrderDispatchCommand(
                companyId: $dispatch->company_id,
                salesOrderLineId: $line->sourceable_id,
                dispatchedDelta: $delta,
            ));
        } catch (SalesOrderOverDispatchedException) {
            throw ValidationException::withMessages([
                'status' => 'Otro despacho se adelantó: el pedido ya no admite toda esta cantidad.',
            ]);
        }
    }

    /**
     * De dónde sale físicamente la mercancía. La línea puede decirlo; si calla,
     * se usa la ubicación por defecto de la bodega, y si la bodega no tiene
     * ninguna el despacho no se confirma: el kardex no mueve saldo sin sitio.
     */
    public function locationFor(Dispatch $dispatch, DispatchLine $line): string
    {
        if (filled($line->location_id)) {
            return $line->location_id;
        }

        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: [
                'warehouse_id' => $dispatch->warehouse_id,
                'is_default' => 'yes',
                'status' => 'active',
            ],
            limit: 1,
            companyId: $dispatch->company_id,
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
