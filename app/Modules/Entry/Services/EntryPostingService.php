<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Models\Entry;
use App\Modules\Entry\Models\EntryLine;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\Item\Commands\ApplyItemAverageCostCommand;
use App\Modules\Item\Services\ItemApplyAverageCostService;
use App\Modules\ItemSerial\Models\ItemSerial;
use App\Modules\PurchaseOrder\Commands\ApplyPurchaseOrderReceiptCommand;
use App\Modules\PurchaseOrder\Services\PurchaseOrderApplyReceiptService;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que la entrada deja de ser un papel y la mercancía está en la
 * bodega.
 *
 * Confirmarla escribe un ingreso en el kardex por cada línea, apunta lo
 * recibido en la orden de compra de origen y recalcula el costo promedio de los
 * artículos que tocó; anularla emite la contrapartida y libera ese pendiente.
 * Mientras está en borrador sus líneas ya están escritas, pero ninguna
 * existencia se ha movido.
 *
 * El ingreso se valora al **landed cost** —el costo de la línea con su parte del
 * flete y de los otros gastos ya dentro—, no al precio que factura el
 * proveedor: lo que cuesta poner la mercancía en la bodega es parte de lo que
 * vale la mercancía.
 *
 * Lo rechazado en inspección no llega al kardex: se queda registrado en la
 * línea para el reclamo al proveedor, pero nunca fue existencia de la empresa.
 */
class EntryPostingService
{
    public function __construct(
        private readonly EntryRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly EntryTraceabilityService $traceability,
        private readonly PurchaseOrderApplyReceiptService $applyToOrderLine,
        private readonly ItemApplyAverageCostService $applyAverageCost,
    ) {}

    /**
     * Mete la mercancía. Cada línea entra a su ubicación al costo con el que
     * quedó puesta en la bodega.
     */
    public function post(Entry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $lines = $this->repository->activeLines($entry);

            foreach ($lines as $line) {
                $this->registerEntry($entry, $line);
                $this->moveOrderLine($entry, $line, round((float) $line->received_quantity, 4));
            }

            $this->refreshAverageCosts($entry, $lines);
        });
    }

    /**
     * Deshace el ingreso: cada movimiento del kardex recibe su contrapartida y
     * la orden recupera lo que había dado por recibido. Ninguna fila se borra.
     */
    public function reverse(Entry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            foreach ($this->postedMovements($entry) as $movement) {
                $this->reversals->execute(new ReverseInventoryMovementCommand(
                    movementId: $movement->id,
                    notes: "Anulación de la entrada {$entry->code}.",
                    createdBy: $entry->created_by,
                ));
            }

            $lines = $this->repository->activeLines($entry);

            foreach ($lines as $line) {
                $this->moveOrderLine($entry, $line, -round((float) $line->received_quantity, 4));
            }

            $this->refreshAverageCosts($entry, $lines);
        });
    }

    /**
     * El ingreso de una línea, en unidad base y al landed cost.
     *
     * Un artículo serializado entra unidad por unidad —cada serie es un asiento
     * de una unidad—, porque el kardex identifica la unidad por su serie y no
     * hay forma de meter tres series en un solo movimiento.
     *
     * Un artículo sin existencia —un servicio colado en la entrada— no llega al
     * kardex: la línea vale para el documento y para el costo, pero no hay
     * saldo que mover. Lo mismo con una línea rechazada por completo.
     */
    private function registerEntry(Entry $entry, EntryLine $line): void
    {
        $quantity = $line->baseReceivedQuantity();

        if ($quantity <= 0.0) {
            return;
        }

        /** Resolver el lote deja escrito `lot_id` en la misma instancia de línea. */
        $serials = $this->traceability->resolve($entry, $line, $line->item);

        try {
            if ($serials !== []) {
                foreach ($serials as $serial) {
                    $this->register($entry, $line, 1.0, $serial);
                }

                return;
            }

            $this->register($entry, $line, $quantity, null);
        } catch (NonInventoriedItemException) {
            // El artículo no lleva existencia: la línea se queda en el documento.
        }
    }

    /** Un asiento del kardex por la cantidad indicada. */
    private function register(Entry $entry, EntryLine $line, float $quantity, ?ItemSerial $serial): void
    {
        $this->movements->execute(new RegisterInventoryMovementCommand(
            companyId: (string) $entry->company_id,
            itemId: $line->item_id,
            warehouseId: $entry->warehouse_id,
            locationId: $this->locationFor($entry, $line),
            type: 'in',
            originType: Entry::MOVEMENT_ORIGIN_TYPE,
            originId: $entry->id,
            quantity: $quantity,
            unitCost: round((float) $line->landed_cost, 6),
            movementDate: $entry->entry_date?->toDateString(),
            originLineId: $line->id,
            lotId: $line->lot_id,
            serialId: $serial?->id,
            notes: $line->notes,
            createdBy: $entry->created_by,
        ));
    }

    /**
     * Movimientos vivos que escribió esta entrada. Las contrapartidas de una
     * anulación anterior quedan fuera: llevan `reversal_of_id`, y anularlas
     * otra vez volvería a meter la mercancía.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(Entry $entry): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => Entry::MOVEMENT_ORIGIN_TYPE,
                'origin_id' => $entry->id,
                'status' => 'active',
            ],
            limit: PHP_INT_MAX,
            companyId: $entry->company_id,
        ));

        return array_values(array_filter(
            $result['data'],
            static fn (InventoryMovement $movement): bool => $movement->reversal_of_id === null,
        ));
    }

    /**
     * Apunta —o libera— lo recibido en la línea de la orden de origen. Una
     * entrada sin orden no tiene dónde apuntarlo.
     */
    private function moveOrderLine(Entry $entry, EntryLine $line, float $delta): void
    {
        if (blank($line->sourceable_id) || $delta === 0.0) {
            return;
        }

        $this->applyToOrderLine->execute(new ApplyPurchaseOrderReceiptCommand(
            companyId: $entry->company_id,
            purchaseOrderLineId: $line->sourceable_id,
            receivedDelta: $delta,
        ));
    }

    /**
     * Recalcula el costo promedio de los artículos que la entrada movió. Va al
     * final y no dentro del bucle: dos líneas del mismo artículo dejan un solo
     * promedio, y el que vale es el de después de haberlas metido todas.
     *
     * @param  array<int, EntryLine>  $lines
     */
    private function refreshAverageCosts(Entry $entry, array $lines): void
    {
        $itemIds = array_unique(array_map(
            static fn (EntryLine $line): string => $line->item_id,
            $lines,
        ));

        foreach ($itemIds as $itemId) {
            $this->applyAverageCost->execute(new ApplyItemAverageCostCommand(
                companyId: $entry->company_id,
                itemId: $itemId,
            ));
        }
    }

    /**
     * A dónde entra físicamente la mercancía. La línea puede decirlo; si calla,
     * se usa la ubicación por defecto de la bodega, y si la bodega no tiene
     * ninguna la entrada no se confirma: el kardex no mueve saldo sin sitio.
     */
    private function locationFor(Entry $entry, EntryLine $line): string
    {
        if (filled($line->location_id)) {
            return $line->location_id;
        }

        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: [
                'warehouse_id' => $entry->warehouse_id,
                'is_default' => 'yes',
                'status' => 'active',
            ],
            limit: 1,
            companyId: $entry->company_id,
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
