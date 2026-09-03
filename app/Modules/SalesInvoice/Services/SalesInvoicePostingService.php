<?php

declare(strict_types=1);

namespace App\Modules\SalesInvoice\Services;

use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\InventoryMovement\Commands\SearchInventoryMovementCommand;
use App\Modules\InventoryMovement\Models\InventoryMovement;
use App\Modules\InventoryMovement\Repositories\Contracts\InventoryMovementRepositoryInterface;
use App\Modules\SalesInvoice\Commands\WriteSalesInvoiceLineCostCommand;
use App\Modules\SalesInvoice\Models\SalesInvoice;
use App\Modules\SalesInvoice\Models\SalesInvoiceLine;
use App\Modules\SalesInvoice\Repositories\Contracts\SalesInvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El momento en que la factura de venta cierra su costo.
 *
 * **No mueve inventario.** La mercancía salió con su despacho, y es ese
 * despacho el que escribió el kardex. Lo que la factura hace al emitirse es
 * leer de esos movimientos a cuánto salió cada artículo y congelarlo: de ese
 * número dependen el `total_cost` y el `margin_amount` de la línea.
 *
 * El costo sale del movimiento real y no del costo que el artículo tenía
 * guardado: entre el despacho y la factura pudo haber entrado mercancía más
 * cara, y lo que se vendió es lo que salió. Una factura sin despacho asociado
 * conserva el costo provisional que la emisión le puso.
 */
class SalesInvoicePostingService
{
    public function __construct(
        private readonly SalesInvoiceRepositoryInterface $repository,
        private readonly InventoryMovementRepositoryInterface $kardex,
    ) {}

    /** Congela el costo contra los movimientos que sacaron la mercancía. */
    public function post(SalesInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $this->freezeCosts($invoice, $this->repository->activeLines($invoice));
        });
    }

    /**
     * Congela el costo de la mercancía vendida contra el kardex.
     *
     * El costo por artículo sale del movimiento del despacho que lo sacó. Un
     * artículo sin movimiento —un servicio, o una factura sin despacho
     * asociado— conserva el costo que la emisión ya le había puesto.
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
     * Los movimientos que dicen a cuánto salió la mercancía: los del despacho
     * que la sacó. Sin despacho no hay contra qué valorar.
     *
     * Las contrapartidas de una anulación quedan fuera: llevan `reversal_of_id`
     * y describen mercancía que volvió, no que salió.
     *
     * @return array<int, InventoryMovement>
     */
    private function costingMovements(SalesInvoice $invoice): array
    {
        if (blank($invoice->dispatch_id)) {
            return [];
        }

        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => Dispatch::MOVEMENT_ORIGIN_TYPE,
                'origin_id' => (string) $invoice->dispatch_id,
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
}
