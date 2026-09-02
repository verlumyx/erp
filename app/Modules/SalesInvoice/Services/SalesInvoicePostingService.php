<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\InventoryMovement\Commands\RegisterInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\ReverseInventoryMovementCommand;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Exceptions\NonInventoriedItemException;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\InventoryMovement\Services\InventoryMovementRegisterService;
use App\Modules\InventoryMovement\Services\InventoryMovementReverseService;
use App\Modules\ItemStock\Exceptions\InsufficientStockException;
use App\Modules\SalesInvoice\Commands\WriteSalesInvoiceLineCostCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que la factura deja de ser un papel y la mercancía sale de la
 * bodega.
 *
 * Con `affects_inventory = 'yes'` —la venta directa, sin despacho previo—
 * confirmarla escribe una salida en el kardex por cada línea; anularla emite la
 * contrapartida. Con `affects_inventory = 'no'` el stock ya salió con su
 * despacho y aquí no se vuelve a mover.
 *
 * En los dos casos la factura sale de aquí con su costo **congelado contra el
 * movimiento real**, no contra el costo que el artículo tenía guardado: el
 * kardex es el que sabe a cuánto salió cada unidad, y de ese número dependen el
 * `total_cost` y el `margin_amount` de la línea. Del movimiento propio si la
 * factura mueve stock; del movimiento del despacho si no.
 */
class SalesInvoicePostingService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
    ) {}

    /**
     * Saca la mercancía y congela el costo. Se comprueba contra la existencia
     * viva: entre la captura y la emisión otro documento pudo haberse llevado
     * lo que esta factura pensaba vender.
     */
    public function post(SalesInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $lines = $this->repository->activeLines($invoice);

            if ($invoice->affects_inventory === 'yes') {
                foreach ($lines as $line) {
                    $this->registerExit($invoice, $line);
                }
            }

            $this->freezeCosts($invoice, $lines);
        });
    }

    /**
     * Deshace la salida: cada movimiento del kardex recibe su contrapartida.
     * Ninguna fila se borra, y el costo congelado se queda escrito: es el que
     * la venta tuvo.
     */
    public function reverse(SalesInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            foreach ($this->postedMovements($invoice) as $movement) {
                $this->reversals->execute(new ReverseInventoryMovementCommand(
                    movementId: $movement->id,
                    notes: "Anulación de la factura {$invoice->code}.",
                    createdBy: $invoice->created_by,
                ));
            }
        });
    }

    /**
     * Un asiento de salida por línea, en unidad base y al promedio vigente: la
     * salida no fija el costo, lo lee.
     *
     * Un artículo sin existencia —un servicio, un flete facturado como línea—
     * no llega al kardex: la línea vale para el documento y para el cobro, pero
     * no hay saldo que mover.
     */
    private function registerExit(SalesInvoice $invoice, SalesInvoiceLine $line): void
    {
        $quantity = round((float) $line->base_quantity, 4);

        if ($quantity <= 0.0) {
            return;
        }

        try {
            $this->movements->execute(new RegisterInventoryMovementCommand(
                companyId: (string) $invoice->company_id,
                itemId: $line->item_id,
                warehouseId: $this->warehouseFor($invoice, $line),
                locationId: $this->locationFor($invoice, $line),
                type: 'out',
                originType: SalesInvoice::MOVEMENT_ORIGIN_TYPE,
                originId: $invoice->id,
                quantity: $quantity,
                movementDate: $invoice->invoice_date?->toDateString(),
                originLineId: $line->id,
                lotId: $line->lot_id,
                serialId: $line->serial_id,
                notes: $line->notes,
                createdBy: $invoice->created_by,
            ));
        } catch (NonInventoriedItemException) {
            // El artículo no lleva existencia: la línea se queda en el documento.
        } catch (InsufficientStockException) {
            throw ValidationException::withMessages([
                'status' => "La bodega no tiene existencia suficiente para la línea {$line->line_number}.",
            ]);
        }
    }

    /**
     * Congela el costo de la mercancía vendida contra el kardex.
     *
     * El costo por artículo sale del movimiento que lo sacó: el propio de la
     * factura cuando ella mueve el stock, y el del despacho cuando la mercancía
     * ya había salido con uno. Un artículo que no dejó movimiento —un servicio,
     * o una factura sin despacho asociado— conserva el costo que la emisión ya
     * le había puesto.
     *
     * @param  array<int, SalesInvoiceLine>  $lines
     */
    private function freezeCosts(SalesInvoice $invoice, array $lines): void
    {
        $costs = $this->unitCosts($invoice);
        $totalCost = 0.0;

        foreach ($lines as $line) {
            $unitCost = $costs[$line->item_id] ?? round((float) $line->unit_cost, 6);
            $lineCost = round((float) $line->base_quantity * $unitCost, 2);
            $totalCost += $lineCost;

            $this->repository->writeLineCost($line, new WriteSalesInvoiceLineCostCommand(
                unitCost: $unitCost,
                totalCost: $lineCost,
                marginAmount: round((float) $line->subtotal - $lineCost, 2),
            ));
        }

        $this->repository->writeTotalCost($invoice, round($totalCost, 2));
    }

    /**
     * Costo unitario con el que salió cada artículo, según el kardex.
     *
     * Dos movimientos del mismo artículo se promedian ponderando por cantidad:
     * dos líneas del mismo artículo pueden haber salido a costos distintos si
     * entre medias entró mercancía más cara.
     *
     * @return array<string, float>
     */
    private function unitCosts(SalesInvoice $invoice): array
    {
        $quantities = [];
        $amounts = [];

        foreach ($this->costingMovements($invoice) as $movement) {
            $quantity = round((float) $movement->quantity, 4);

            if ($quantity <= 0) {
                continue;
            }

            $quantities[$movement->item_id] = ($quantities[$movement->item_id] ?? 0.0) + $quantity;
            $amounts[$movement->item_id] = ($amounts[$movement->item_id] ?? 0.0)
                + $quantity * (float) $movement->unit_cost;
        }

        $costs = [];

        foreach ($quantities as $itemId => $quantity) {
            $costs[$itemId] = round($amounts[$itemId] / $quantity, 6);
        }

        return $costs;
    }

    /**
     * Los movimientos que dicen a cuánto salió la mercancía: los propios de la
     * factura, o los del despacho que la sacó antes.
     *
     * @return array<int, InventoryMovement>
     */
    private function costingMovements(SalesInvoice $invoice): array
    {
        if ($invoice->affects_inventory === 'yes') {
            return $this->postedMovements($invoice);
        }

        if (blank($invoice->dispatch_id)) {
            return [];
        }

        return $this->liveMovements($invoice, Dispatch::MOVEMENT_ORIGIN_TYPE, (string) $invoice->dispatch_id);
    }

    /**
     * Movimientos vivos que escribió esta factura. Las contrapartidas de una
     * anulación anterior quedan fuera: llevan `reversal_of_id`, y anularlas otra
     * vez volvería a sacar la mercancía.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(SalesInvoice $invoice): array
    {
        return $this->liveMovements($invoice, SalesInvoice::MOVEMENT_ORIGIN_TYPE, $invoice->id);
    }

    /**
     * @return array<int, InventoryMovement>
     */
    private function liveMovements(SalesInvoice $invoice, string $originType, string $originId): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => $originType,
                'origin_id' => $originId,
                'status' => 'active',
            ],
            limit: PHP_INT_MAX,
            companyId: $invoice->company_id,
        ));

        return array_values(array_filter(
            $result['data'],
            static fn (InventoryMovement $movement): bool => $movement->reversal_of_id === null,
        ));
    }

    /** La bodega de la línea manda sobre la de la cabecera, si la trae. */
    private function warehouseFor(SalesInvoice $invoice, SalesInvoiceLine $line): string
    {
        return filled($line->warehouse_id) ? $line->warehouse_id : $invoice->warehouse_id;
    }

    /**
     * De dónde sale físicamente la mercancía: la ubicación por defecto de la
     * bodega. Sin ninguna, la factura no se emite: el kardex no mueve saldo sin
     * sitio.
     */
    private function locationFor(SalesInvoice $invoice, SalesInvoiceLine $line): string
    {
        $result = $this->locations->search(new SearchWarehouseLocationCommand(
            filters: [
                'warehouse_id' => $this->warehouseFor($invoice, $line),
                'is_default' => 'yes',
                'status' => 'active',
            ],
            limit: 1,
            companyId: $invoice->company_id,
        ));

        $location = $result['data'][0] ?? null;

        if (! $location instanceof WarehouseLocation) {
            throw ValidationException::withMessages([
                'status' => "La bodega de la línea {$line->line_number} no tiene ubicación por defecto.",
            ]);
        }

        return $location->id;
    }
}
