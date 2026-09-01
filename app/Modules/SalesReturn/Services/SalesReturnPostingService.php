<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\SalesInvoice\Commands\ApplySalesInvoiceReturnCommand;
use App\Modules\SalesInvoice\Services\SalesInvoiceApplyReturnService;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Models\SalesReturnLine;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que la devolución deja de ser un papel y la mercancía vuelve a
 * la bodega.
 *
 * Confirmarla escribe una entrada en el kardex por cada línea y apunta lo
 * devuelto en la factura de origen; anularla emite la contrapartida y libera
 * ese cupo. Mientras está en borrador sus líneas ya están escritas, pero
 * ninguna existencia se ha movido: es el mismo trato que la factura le da a
 * las suyas.
 *
 * La entrada se valora **al costo de la venta original** —el `unit_cost` que la
 * línea congeló al guardarse— y no al promedio vigente: devolver mercancía no
 * puede inventar ni destruir margen.
 *
 * Las líneas que vuelven para destruirse (`scrap`) no llegan al kardex: no
 * reingresan a ninguna existencia, y la pérdida se registra por Ajuste. Cuentan
 * igual para el documento, para la nota de crédito y para el cupo devuelto de
 * la factura: al cliente se le acredita lo que devolvió, esté o no en estado de
 * volver al almacén.
 */
class SalesReturnPostingService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly SalesInvoiceApplyReturnService $applyToInvoiceLine,
    ) {}

    /**
     * Mete la mercancía. Cada línea entra a su ubicación con el costo que la
     * venta le congeló.
     */
    public function post(SalesReturn $return): void
    {
        DB::transaction(function () use ($return): void {
            foreach ($this->repository->activeLines($return) as $line) {
                $this->registerEntry($return, $line);
                $this->moveInvoiceLine($return, $line, round((float) $line->quantity, 4));
            }
        });
    }

    /**
     * Deshace la entrada: cada movimiento del kardex recibe su contrapartida y
     * la factura recupera el cupo devuelto. Ninguna fila se borra.
     */
    public function reverse(SalesReturn $return): void
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
     * Un asiento de entrada por línea, en unidad base y al costo de la venta.
     *
     * Un artículo sin existencia —un servicio colado en la devolución— no llega
     * al kardex: la línea vale para el documento y para la nota de crédito, pero
     * no hay saldo que mover. Lo mismo con lo que vuelve para destruirse.
     */
    private function registerEntry(SalesReturn $return, SalesReturnLine $line): void
    {
        if ($return->conditionFor($line) === SalesReturn::SCRAP_CONDITION) {
            return;
        }

        try {
            $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $return->company_id,
                itemId: $line->item_id,
                warehouseId: $return->warehouse_id,
                locationId: $this->locationFor($return, $line),
                type: 'in',
                originType: SalesReturn::MOVEMENT_ORIGIN_TYPE,
                originId: $return->id,
                quantity: round((float) $line->base_quantity, 4),
                unitCost: round((float) $line->unit_cost, 6),
                movementDate: $return->return_date?->toDateString(),
                originLineId: $line->id,
                lotId: $line->lot_id,
                serialId: $line->serial_id,
                notes: $line->notes,
                createdBy: $return->created_by,
            ));
        } catch (NonInventoriedItemException) {
            return;
        }
    }

    /**
     * Movimientos vivos que escribió esta devolución. Las contrapartidas de una
     * anulación anterior quedan fuera: llevan `reversal_of_id`, y anularlas otra
     * vez volvería a meter la mercancía.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(SalesReturn $return): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => SalesReturn::MOVEMENT_ORIGIN_TYPE,
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
    private function moveInvoiceLine(SalesReturn $return, SalesReturnLine $line, float $delta): void
    {
        if (blank($line->sales_invoice_line_id) || $delta === 0.0) {
            return;
        }

        $this->applyToInvoiceLine->execute(new ApplySalesInvoiceReturnCommand(
            companyId: $return->company_id,
            salesInvoiceLineId: $line->sales_invoice_line_id,
            returnedDelta: $delta,
        ));
    }

    /**
     * A dónde entra físicamente la mercancía. La línea puede decirlo; si calla,
     * se usa la ubicación por defecto de la bodega, y si la bodega no tiene
     * ninguna la devolución no se confirma: el kardex no mueve saldo sin sitio.
     */
    private function locationFor(SalesReturn $return, SalesReturnLine $line): string
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
