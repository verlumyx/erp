<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\WriteDispatchLineCostCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Models\DispatchLine;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\SalesOrder\Commands\ApplySalesOrderDispatchCommand;
use App\Modules\SalesOrder\Exceptions\SalesOrderOverDispatchedException;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Services\SalesOrderApplyDispatchService;
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
    ) {}

    /**
     * Saca la mercancía. Cada línea sale de su ubicación al promedio vigente.
     */
    public function post(Dispatch $dispatch): void
    {
        DB::transaction(function () use ($dispatch): void {
            foreach ($this->repository->activeLines($dispatch) as $line) {
                $this->registerExit($dispatch, $line);
                $this->moveOrderLine($dispatch, $line, round((float) $line->quantity, 4));
            }

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
        });
    }

    /**
     * Un asiento de salida por línea, en unidad base.
     *
     * Un artículo sin existencia —un servicio colado en el despacho— no llega
     * al kardex: la línea vale para la guía, pero no hay saldo que mover.
     */
    private function registerExit(Dispatch $dispatch, DispatchLine $line): void
    {
        try {
            $movement = $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $dispatch->company_id,
                itemId: $line->item_id,
                warehouseId: $dispatch->warehouse_id,
                locationId: $this->locationFor($dispatch, $line),
                type: 'out',
                originType: Dispatch::MOVEMENT_ORIGIN_TYPE,
                originId: $dispatch->id,
                quantity: round((float) $line->base_quantity, 4),
                /** Sin costo impuesto: la salida se valora al promedio vigente. */
                unitCost: null,
                movementDate: $dispatch->dispatch_date?->toDateString(),
                originLineId: $line->id,
                lotId: $line->lot_id,
                serialId: $line->serial_id,
                notes: $line->notes,
                createdBy: $dispatch->created_by,
            ));
        } catch (NonInventoriedItemException) {
            return;
        } catch (InsufficientStockException) {
            throw ValidationException::withMessages([
                'status' => 'No hay existencia suficiente en la bodega para despachar todas las líneas.',
            ]);
        }

        /** El costo con el que salió de verdad se guarda en la línea. */
        $this->repository->writeLineCost(
            $line,
            new WriteDispatchLineCostCommand(unitCost: (float) $movement->unit_cost),
        );
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
