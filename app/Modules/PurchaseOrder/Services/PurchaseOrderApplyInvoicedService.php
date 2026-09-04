<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\Commands\ApplyPurchaseOrderInvoicedCommand;
use App\Modules\PurchaseOrder\Commands\WritePurchaseOrderLineInvoicedCommand;
use App\Modules\PurchaseOrder\Exceptions\PurchaseOrderLineNotFoundException;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que mueve `invoiced_quantity` de una línea de orden de
 * compra.
 *
 * Lo llaman las facturas de compra al confirmarse, y otra vez con el signo
 * contrario cuando esa factura se anula. Lo facturado no puede bajar de cero;
 * **sí** puede pasarse de lo pedido, igual que lo recibido: un proveedor factura
 * a veces de más y el ERP tiene que poder reflejarlo.
 *
 * Y como lo facturado es la otra cuenta que cierra la orden, cada movimiento
 * vuelve a resolver su estado: `PurchaseOrderSettleStatusService`.
 *
 * La transacción es anidable: llamado desde la factura se suma a la suya como
 * savepoint.
 */
class PurchaseOrderApplyInvoicedService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
        private readonly PurchaseOrderSettleStatusService $settle,
    ) {}

    public function execute(ApplyPurchaseOrderInvoicedCommand $command): PurchaseOrderLine
    {
        return DB::transaction(function () use ($command): PurchaseOrderLine {
            $line = $this->repository->lockLineById($command->purchaseOrderLineId, $command->companyId);

            if ($line === null) {
                throw new PurchaseOrderLineNotFoundException;
            }

            $invoiced = max(round((float) $line->invoiced_quantity + $command->invoicedDelta, 4), 0);

            $line = $this->repository->writeLineInvoiced($line, new WritePurchaseOrderLineInvoicedCommand(
                invoicedQuantity: $invoiced,
            ));

            $this->repository->refreshInvoicedPercent($line->purchase_order_id);
            $this->settle->execute($line->purchase_order_id);

            return $line;
        });
    }
}
