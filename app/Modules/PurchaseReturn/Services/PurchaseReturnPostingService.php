<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\PurchaseInvoice\Commands\ApplyPurchaseInvoiceReturnCommand;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseInvoice\Services\PurchaseInvoiceApplyReturnService;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Models\PurchaseReturnLine;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que la devolución deja de ser un papel y la mercancía sale de
 * la bodega.
 *
 * Confirmarla escribe una salida en el kardex por cada línea y apunta lo
 * devuelto en la factura de origen; anularla emite la contrapartida y libera
 * ese cupo. Mientras está en borrador sus líneas ya están escritas, pero
 * ninguna existencia se ha movido: es el mismo trato que la factura le da a
 * las suyas.
 *
 * La salida se valora **al costo de la compra original** —el `landed_cost` de
 * la línea facturada, o su precio si no lo tiene— y no al promedio vigente:
 * devolver mercancía no puede inventar ni destruir margen.
 */
class PurchaseReturnPostingService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly PurchaseInvoiceApplyReturnService $applyToInvoiceLine,
    ) {}

    /**
     * Saca la mercancía. Se vuelve a comprobar contra la existencia viva: entre
     * la captura y la confirmación otro documento pudo haberse llevado lo que
     * esta devolución pensaba sacar.
     */
    public function post(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            foreach ($this->repository->activeLines($return) as $line) {
                $this->registerExit($return, $line);
                $this->moveInvoiceLine($return, $line, round((float) $line->quantity, 4));
            }
        });
    }

    /**
     * Deshace la salida: cada movimiento del kardex recibe su contrapartida y
     * la factura recupera el cupo devuelto. Ninguna fila se borra.
     */
    public function reverse(PurchaseReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            foreach ($this->postedMovements($return) as $movement) {
                $this->reversals->execute(new ReverseInventoryMovementCommand(
                    movementId: $movement->id,
                    notes: "Anulación de la devolución {$return->code}.",
                    createdBy: $return->created_by,
                ));
            }

            foreach ($this->repository->activeLines($return) as $line) {
                $this->moveInvoiceLine($return, $line, -round((float) $line->quantity, 4));
            }
        });
    }

    /**
     * Un asiento de salida por línea, en unidad base y al costo de la compra.
     *
     * Un artículo sin existencia —un servicio colado en la devolución— no llega
     * al kardex: la línea vale para el documento y para la nota de crédito, pero
     * no hay saldo que mover.
     */
    private function registerExit(PurchaseReturn $return, PurchaseReturnLine $line): void
    {
        try {
            $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $return->company_id,
                itemId: $line->item_id,
                warehouseId: $return->warehouse_id,
                locationId: $this->locationFor($return, $line),
                type: 'out',
                originType: PurchaseReturn::MOVEMENT_ORIGIN_TYPE,
                originId: $return->id,
                quantity: round((float) $line->base_quantity, 4),
                unitCost: $this->originalCost($line),
                movementDate: $return->return_date?->toDateString(),
                originLineId: $line->id,
                lotId: $line->lot_id,
                serialId: $line->serial_id,
                notes: $line->notes,
                createdBy: $return->created_by,
            ));
        } catch (NonInventoriedItemException) {
            return;
        } catch (InsufficientStockException) {
            throw ValidationException::withMessages([
                'status' => "La bodega no tiene existencia suficiente para devolver la línea {$line->line_number}.",
            ]);
        }
    }

    /**
     * Movimientos vivos que escribió esta devolución. Las contrapartidas de una
     * anulación anterior quedan fuera: llevan `reversal_of_id`, y anularlas otra
     * vez volvería a sacar la mercancía.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(PurchaseReturn $return): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => PurchaseReturn::MOVEMENT_ORIGIN_TYPE,
                'origin_id' => $return->id,
                'status' => 'active',
            ],
            limit: PHP_INT_MAX,
            companyId: $return->company_id,
        ));

        return array_values(array_filter(
            $result['data'],
            static fn (InventoryMovement $movement): bool => $movement->reversal_of_id === null,
        ));
    }

    /**
     * Apunta —o libera— lo devuelto en la línea de la factura de origen. Una
     * devolución sin factura no tiene dónde apuntarlo.
     */
    private function moveInvoiceLine(PurchaseReturn $return, PurchaseReturnLine $line, float $delta): void
    {
        if (blank($line->purchase_invoice_line_id) || $delta === 0.0) {
            return;
        }

        $this->applyToInvoiceLine->execute(new ApplyPurchaseInvoiceReturnCommand(
            companyId: $return->company_id,
            purchaseInvoiceLineId: $line->purchase_invoice_line_id,
            returnedDelta: $delta,
        ));
    }

    /**
     * Costo unitario con el que la mercancía entró: el costo final de la línea
     * facturada, con flete y gastos ya prorrateados, y si no lo tiene su precio
     * de compra. Sin factura de origen manda el precio de la propia devolución.
     */
    private function originalCost(PurchaseReturnLine $line): float
    {
        $invoiceLine = $line->purchaseInvoiceLine;

        if ($invoiceLine instanceof PurchaseInvoiceLine) {
            $landed = round((float) $invoiceLine->landed_cost, 6);

            return $landed > 0 ? $landed : round((float) $invoiceLine->unit_price, 6);
        }

        return round((float) $line->unit_price, 6);
    }

    /**
     * De dónde sale físicamente la mercancía. La línea puede decirlo; si calla,
     * se usa la ubicación por defecto de la bodega, y si la bodega no tiene
     * ninguna la devolución no se confirma: el kardex no mueve saldo sin sitio.
     */
    private function locationFor(PurchaseReturn $return, PurchaseReturnLine $line): string
    {
        if (filled($line->location_id)) {
            return $line->location_id;
        }

        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: [
                'warehouse_id' => $return->warehouse_id,
                'is_default' => 'yes',
                'status' => 'active',
            ],
            limit: 1,
            companyId: $return->company_id,
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
