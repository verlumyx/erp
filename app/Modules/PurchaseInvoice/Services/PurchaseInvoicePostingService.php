<?php

declare(strict_types=1);

namespace App\Modules\PurchaseInvoice\Services;

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
use App\Modules\PurchaseInvoice\Models\PurchaseInvoice;
use App\Modules\PurchaseInvoice\Models\PurchaseInvoiceLine;
use App\Modules\PurchaseInvoice\Repositories\Contracts\PurchaseInvoiceRepositoryInterface;
use App\Modules\PurchaseOrder\Commands\ApplyPurchaseOrderInvoicedCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Services\PurchaseOrderApplyInvoicedService;
use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Services\SupplierApplyBalanceService;
use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Models\WarehouseLocation;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El momento en que la factura de compra deja de ser un papel y se convierte en
 * deuda con el proveedor.
 *
 * Confirmarla carga su cuenta por pagar, apunta lo facturado en la orden que la
 * originó y —solo si la mercancía no entró ya con una entrada previa— la mete
 * en el kardex y recalcula el costo promedio de los artículos que tocó.
 * Anularla hace exactamente lo contrario. Mientras está en borrador sus líneas
 * ya están escritas, pero ningún saldo se ha movido.
 *
 * Lo que se le debe al proveedor no es el total de la factura sino
 * `total - withholding_amount`: la retención es una parte del impuesto que se
 * entera al fisco en vez de pagarse al proveedor, así que baja lo que se le
 * paga, no lo que la factura vale.
 *
 * El ingreso se valora al **landed cost** —el costo de la línea con su parte del
 * flete y de los otros gastos ya dentro—, igual que en una entrada: lo que
 * cuesta poner la mercancía en la bodega es parte de lo que vale la mercancía.
 */
class PurchaseInvoicePostingService
{
    public function __construct(
        private readonly PurchaseInvoiceRepositoryInterface $repository,
        private readonly InventoryMovementRegisterService $movements,
        private readonly InventoryMovementReverseService $reversals,
        private readonly InventoryMovementRepositoryInterface $kardex,
        private readonly WarehouseLocationRepositoryInterface $locations,
        private readonly SupplierApplyBalanceService $applyToSupplier,
        private readonly PurchaseOrderApplyInvoicedService $applyToOrderLine,
        private readonly ItemApplyAverageCostService $applyAverageCost,
    ) {}

    /** Emite la factura: genera la deuda y, si toca, mete la mercancía. */
    public function post(PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            $lines = $this->repository->activeLines($invoice);

            foreach ($lines as $line) {
                $this->registerEntry($invoice, $line);
                $this->moveOrderLine($invoice, $line, round((float) $line->quantity, 4));
            }

            $this->refreshAverageCosts($invoice, $lines);
            $this->moveSupplierBalance($invoice, $this->payableAmount($invoice));
        });
    }

    /**
     * Deshace la emisión: el proveedor deja de tener esa cuenta por cobrarnos,
     * la orden recupera lo que había dado por facturado y cada movimiento del
     * kardex recibe su contrapartida. Ninguna fila se borra.
     */
    public function reverse(PurchaseInvoice $invoice): void
    {
        DB::transaction(function () use ($invoice): void {
            foreach ($this->postedMovements($invoice) as $movement) {
                $this->reversals->execute(new ReverseInventoryMovementCommand(
                    movementId: $movement->id,
                    notes: "Anulación de la factura de compra {$invoice->code}.",
                    createdBy: $invoice->created_by,
                ));
            }

            $lines = $this->repository->activeLines($invoice);

            foreach ($lines as $line) {
                $this->moveOrderLine($invoice, $line, -round((float) $line->quantity, 4));
            }

            $this->refreshAverageCosts($invoice, $lines);
            $this->moveSupplierBalance($invoice, -$this->payableAmount($invoice));
        });
    }

    /**
     * El ingreso de una línea, en unidad base y al landed cost.
     *
     * Con `affects_inventory = 'no'` la mercancía ya entró con su entrada y esta
     * factura solo documenta la deuda: no se vuelve a meter.
     *
     * Un artículo sin existencia —un servicio, un gasto facturado en la misma
     * factura— no llega al kardex: la línea vale para el documento y para la
     * deuda, pero no hay saldo que mover.
     */
    private function registerEntry(PurchaseInvoice $invoice, PurchaseInvoiceLine $line): void
    {
        if ($invoice->affects_inventory !== 'yes') {
            return;
        }

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
                type: 'in',
                originType: PurchaseInvoice::MOVEMENT_ORIGIN_TYPE,
                originId: $invoice->id,
                quantity: $quantity,
                unitCost: round((float) $line->landed_cost, 6),
                movementDate: $invoice->invoice_date?->toDateString(),
                originLineId: $line->id,
                lotId: $line->lot_id,
                notes: $line->notes,
                createdBy: $invoice->created_by,
            ));
        } catch (NonInventoriedItemException) {
            // El artículo no lleva existencia: la línea se queda en el documento.
        }
    }

    /**
     * Movimientos vivos que escribió esta factura. Las contrapartidas de una
     * anulación anterior quedan fuera: llevan `reversal_of_id`, y anularlas otra
     * vez volvería a meter la mercancía.
     *
     * @return array<int, InventoryMovement>
     */
    private function postedMovements(PurchaseInvoice $invoice): array
    {
        $result = $this->kardex->search(new SearchInventoryMovementCommand(
            filters: [
                'origin_type' => PurchaseInvoice::MOVEMENT_ORIGIN_TYPE,
                'origin_id' => $invoice->id,
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

    /**
     * Apunta —o libera— lo facturado en la línea de la orden de origen. Una
     * factura directa, sin orden previa, no tiene dónde apuntarlo.
     */
    private function moveOrderLine(PurchaseInvoice $invoice, PurchaseInvoiceLine $line, float $delta): void
    {
        if ($line->sourceable_type !== PurchaseOrderLine::MORPH_ALIAS) {
            return;
        }

        if (blank($line->sourceable_id) || $delta === 0.0) {
            return;
        }

        $this->applyToOrderLine->execute(new ApplyPurchaseOrderInvoicedCommand(
            companyId: $invoice->company_id,
            purchaseOrderLineId: $line->sourceable_id,
            invoicedDelta: $delta,
        ));
    }

    /**
     * Recalcula el costo promedio de los artículos que la factura movió. Va al
     * final y no dentro del bucle: dos líneas del mismo artículo dejan un solo
     * promedio, y el que vale es el de después de haberlas metido todas.
     *
     * @param  array<int, PurchaseInvoiceLine>  $lines
     */
    private function refreshAverageCosts(PurchaseInvoice $invoice, array $lines): void
    {
        if ($invoice->affects_inventory !== 'yes') {
            return;
        }

        $itemIds = array_unique(array_map(
            static fn (PurchaseInvoiceLine $line): string => $line->item_id,
            $lines,
        ));

        foreach ($itemIds as $itemId) {
            $this->applyAverageCost->execute(new ApplyItemAverageCostCommand(
                companyId: $invoice->company_id,
                itemId: $itemId,
            ));
        }
    }

    /** Lo que se le debe al proveedor por esta factura. */
    private function payableAmount(PurchaseInvoice $invoice): float
    {
        return round((float) $invoice->total - (float) $invoice->withholding_amount, 2);
    }

    /** Carga (o descarga) la cuenta por pagar del proveedor. */
    private function moveSupplierBalance(PurchaseInvoice $invoice, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $this->applyToSupplier->execute(new ApplySupplierBalanceCommand(
            companyId: $invoice->company_id,
            supplierId: $invoice->supplier_id,
            currentBalanceDelta: $delta,
        ));
    }

    /** La bodega de la línea manda sobre la de la cabecera, si la trae. */
    private function warehouseFor(PurchaseInvoice $invoice, PurchaseInvoiceLine $line): string
    {
        return filled($line->warehouse_id) ? $line->warehouse_id : $invoice->warehouse_id;
    }

    /**
     * A dónde entra físicamente la mercancía: la ubicación por defecto de la
     * bodega. Sin ninguna, la factura no se confirma: el kardex no mueve saldo
     * sin sitio.
     */
    private function locationFor(PurchaseInvoice $invoice, PurchaseInvoiceLine $line): string
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
