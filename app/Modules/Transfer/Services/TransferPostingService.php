<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\Transfer\Commands\WriteTransferLineCostCommand;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que el traslado deja de ser un papel y la mercancía sale de la
 * bodega de origen.
 *
 * Un traslado **no cambia el valor del inventario**, solo su ubicación, así que
 * cada salida lleva pegada su entrada: la mercancía nunca desaparece del
 * kardex mientras viaja. Con bodega de tránsito la entrada cae en ella y la
 * segunda pareja de movimientos la escribe la recepción; sin bodega de
 * tránsito, salida y entrada son simultáneas y la mercancía llega al destino en
 * el mismo acto.
 *
 * La salida **no lleva costo impuesto**: la valora el kardex con el promedio
 * vigente del origen. Ese costo se congela en la línea y es el que viaja con la
 * mercancía: el destino la recibe con él, no con el suyo, que es lo que hace
 * que trasladar no invente ni destruya valor.
 */
class TransferPostingService
{
    public function __construct(
        private readonly TransferRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
    ) {}

    /**
     * Saca la mercancía del origen y la pone donde toca: en la bodega de
     * tránsito si el traslado no es inmediato, o en la de destino si lo es.
     */
    public function post(Transfer $transfer, ?string $sentBy = null): void
    {
        DB::transaction(function () use ($transfer, $sentBy): void {
            foreach ($this->repository->activeLines($transfer) as $line) {
                $this->moveLine($transfer, $line);
            }

            $this->repository->writeShipment($transfer, $sentBy ?? $transfer->created_by);
            $this->repository->refreshTotals($transfer);
        });
    }

    /**
     * Deshace el traslado: cada movimiento del kardex recibe su contrapartida y
     * la mercancía vuelve a estar donde estaba. Ninguna fila se borra.
     *
     * Las contrapartidas se emiten **de la última a la primera**. Deshacer un
     * viaje en orden cronológico dejaría la bodega de tránsito en negativo —se
     * le quitaría la entrada antes de devolverle la salida— y una bodega que no
     * admite saldo negativo tumbaría la anulación entera.
     */
    public function reverse(Transfer $transfer): void
    {
        DB::transaction(function () use ($transfer): void {
            foreach ($this->postedMovements($transfer) as $movement) {
                $this->reversals->execute(new ReverseInventoryMovementCommand(
                    movementId: $movement->id,
                    notes: "Anulación del traslado {$transfer->code}.",
                    createdBy: $transfer->created_by,
                ));
            }
        });
    }

    /**
     * La pareja de asientos de una línea: la salida del origen y la entrada en
     * la primera parada del viaje.
     *
     * Un artículo sin existencia —un servicio colado en el traslado— no llega
     * al kardex: no hay saldo que mover ni de un lado ni del otro.
     */
    private function moveLine(Transfer $transfer, TransferLine $line): void
    {
        $quantity = round((float) $line->base_quantity, 4);
        $date = $transfer->transfer_date?->toDateString();

        try {
            $exit = $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $transfer->company_id,
                itemId: $line->item_id,
                warehouseId: $transfer->origin_warehouse_id,
                locationId: $this->locationFor($transfer, $line->origin_location_id, $transfer->origin_warehouse_id),
                type: 'transfer_out',
                originType: Transfer::MOVEMENT_ORIGIN_TYPE,
                originId: $transfer->id,
                quantity: $quantity,
                /** Sin costo impuesto: la salida se valora al promedio del origen. */
                unitCost: null,
                movementDate: $date,
                originLineId: $line->id,
                lotId: $line->lot_id,
                serialId: $line->serial_id,
                notes: $line->notes,
                createdBy: $transfer->created_by,
            ));
        } catch (NonInventoriedItemException) {
            return;
        } catch (InsufficientStockException) {
            throw ValidationException::withMessages([
                'status' => 'No hay existencia suficiente en la bodega de origen para trasladar todas las líneas.',
            ]);
        }

        /** El costo con el que salió de verdad es el que viaja con la mercancía. */
        $this->repository->writeLineCost($line, new WriteTransferLineCostCommand(
            unitCost: (float) $exit->unit_cost,
            sentQuantity: round((float) $line->quantity, 4),
        ));

        /**
         * Primera parada del viaje. Con tránsito la mercancía se queda ahí
         * hasta que alguien la reciba; sin él ya está en su destino.
         */
        $arrivalWarehouseId = $transfer->isTwoStep()
            ? (string) $transfer->transit_warehouse_id
            : $transfer->destination_warehouse_id;

        $arrivalLocationId = $transfer->isTwoStep()
            ? null
            : $line->destination_location_id;

        $this->registerArrival(
            transfer: $transfer,
            line: $line,
            warehouseId: $arrivalWarehouseId,
            locationId: $arrivalLocationId,
            quantity: $quantity,
            unitCost: (float) $exit->unit_cost,
            movementDate: $date,
        );
    }

    /**
     * La entrada de la mercancía en una bodega, siempre al costo con el que
     * salió del origen. Es lo que impide que trasladar cambie el valor del
     * inventario.
     */
    public function registerArrival(
        Transfer $transfer,
        TransferLine $line,
        string $warehouseId,
        ?string $locationId,
        float $quantity,
        float $unitCost,
        ?string $movementDate = null,
    ): void {
        if ($quantity <= 0) {
            return;
        }

        try {
            $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $transfer->company_id,
                itemId: $line->item_id,
                warehouseId: $warehouseId,
                locationId: $this->locationFor($transfer, $locationId, $warehouseId),
                type: 'transfer_in',
                originType: Transfer::MOVEMENT_ORIGIN_TYPE,
                originId: $transfer->id,
                quantity: round($quantity, 4),
                /** El costo viaja con la mercancía: entra con el del origen. */
                unitCost: round($unitCost, 6),
                movementDate: $movementDate,
                originLineId: $line->id,
                lotId: $line->lot_id,
                serialId: $line->serial_id,
                notes: $line->notes,
                createdBy: $transfer->created_by,
            ));
        } catch (NonInventoriedItemException) {
            // Un artículo sin existencia no salió del kardex: tampoco entra.
        }
    }

    /**
     * La salida de una bodega intermedia, al mismo costo con el que entró en
     * ella. Solo la usa la recepción de un traslado en dos pasos, para vaciar
     * el tránsito de lo que llegó al destino.
     */
    public function registerTransitExit(
        Transfer $transfer,
        TransferLine $line,
        float $quantity,
        float $unitCost,
        ?string $movementDate = null,
    ): void {
        if ($quantity <= 0) {
            return;
        }

        try {
            $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $transfer->company_id,
                itemId: $line->item_id,
                warehouseId: (string) $transfer->transit_warehouse_id,
                locationId: $this->locationFor($transfer, null, (string) $transfer->transit_warehouse_id),
                type: 'transfer_out',
                originType: Transfer::MOVEMENT_ORIGIN_TYPE,
                originId: $transfer->id,
                quantity: round($quantity, 4),
                unitCost: round($unitCost, 6),
                movementDate: $movementDate,
                originLineId: $line->id,
                lotId: $line->lot_id,
                serialId: $line->serial_id,
                notes: $line->notes,
                createdBy: $transfer->created_by,
            ));
        } catch (NonInventoriedItemException) {
            // Un artículo sin existencia nunca entró al tránsito.
        } catch (InsufficientStockException) {
            throw ValidationException::withMessages([
                'lines' => 'La bodega de tránsito no tiene esa mercancía: revisa lo que dices haber recibido.',
            ]);
        }
    }

    /**
     * Movimientos vivos que escribió este traslado, **del más reciente al más
     * antiguo**. Las contrapartidas de una anulación anterior quedan fuera:
     * llevan `reversal_of_id`, y anularlas otra vez volvería a mover la
     * mercancía.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(Transfer $transfer): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => Transfer::MOVEMENT_ORIGIN_TYPE,
                'origin_id' => $transfer->id,
                'status' => 'active',
            ],
            limit: PHP_INT_MAX,
            companyId: $transfer->company_id,
        ));

        return array_values(array_filter(
            $result['data'],
            static fn (InventoryMovement $movement): bool => $movement->reversal_of_id === null,
        ));
    }

    /**
     * De qué sitio de la bodega se mueve la mercancía. La línea puede decirlo;
     * si calla, se usa la ubicación por defecto de esa bodega, y si la bodega no
     * tiene ninguna el traslado no se confirma: el kardex no mueve saldo sin
     * sitio.
     */
    public function locationFor(Transfer $transfer, ?string $locationId, string $warehouseId): string
    {
        if (filled($locationId)) {
            return $locationId;
        }

        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: [
                'warehouse_id' => $warehouseId,
                'is_default' => 'yes',
                'status' => 'active',
            ],
            limit: 1,
            companyId: $transfer->company_id,
        ));

        $location = $result['data'][0] ?? null;

        if (! $location instanceof WarehouseLocation) {
            throw ValidationException::withMessages([
                'status' => 'Alguna de las bodegas del traslado no tiene ubicación por defecto: indícala en cada línea antes de confirmar.',
            ]);
        }

        return $location->id;
    }
}
