<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\Commands\ApplyPurchaseOrderReceiptCommand;
use App\Modules\PurchaseOrder\Commands\WritePurchaseOrderLineReceiptCommand;
use App\Modules\PurchaseOrder\Exceptions\InvalidPurchaseOrderReceiptException;
use App\Modules\PurchaseOrder\Exceptions\PurchaseOrderLineNotFoundException;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que mueve `received_quantity` de una línea de orden de
 * compra.
 *
 * Lo llaman las entradas al confirmarse, y otra vez con el signo contrario
 * cuando esa entrada se anula. Lo recibido no puede bajar de cero; **sí** puede
 * pasarse de lo pedido, porque un proveedor a veces despacha de más y el ERP
 * tiene que poder reflejarlo. Quién puede permitirlo lo decide el documento que
 * recibe —el permiso `entries.allow-over-receipt`—, no esta puerta.
 *
 * Recibir **consume lo anunciado**: la mercancía que entró a la bodega dejó de
 * estar en camino. Anular la entrada la vuelve a poner en camino, porque la
 * orden sigue esperándola.
 *
 * La transacción es anidable: llamado desde la entrada se suma a la transacción
 * abierta como savepoint.
 */
class PurchaseOrderApplyReceiptService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
        private readonly PurchaseOrderIncomingService $incoming,
    ) {}

    public function execute(ApplyPurchaseOrderReceiptCommand $command): PurchaseOrderLine
    {
        return DB::transaction(function () use ($command): PurchaseOrderLine {
            $line = $this->repository->lockLineById($command->purchaseOrderLineId, $command->companyId);

            if ($line === null) {
                throw new PurchaseOrderLineNotFoundException;
            }

            $received = round((float) $line->received_quantity + $command->receivedDelta, 4);

            if ($received < 0) {
                throw new InvalidPurchaseOrderReceiptException;
            }

            $line = $this->repository->writeLineReceipt($line, new WritePurchaseOrderLineReceiptCommand(
                receivedQuantity: $received,
                pendingQuantity: max(round((float) $line->quantity - $received, 4), 0),
            ));

            /**
             * Y lo que ya llegó deja de estar en camino: la entrada lo sumó a
             * la existencia, así que dejarlo anunciado lo contaría dos veces.
             */
            $this->consumeIncoming($line, $command->receivedDelta);

            $this->repository->refreshReceivedPercent($line->purchase_order_id);

            return $line;
        });
    }
    /**
     * Descuenta —o devuelve— de la mercancía en camino lo que la entrada acaba
     * de mover, en unidad base y en la bodega de la orden.
     */
    private function consumeIncoming(PurchaseOrderLine $line, float $receivedDelta): void
    {
        $order = $line->purchaseOrder;

        if ($order === null || $receivedDelta === 0.0) {
            return;
        }

        $ordered = round((float) $line->quantity, 4);

        if ($ordered <= 0) {
            return;
        }

        $this->incoming->receive(
            $order->company_id,
            $order->warehouse_id,
            $line->item_id,
            round($receivedDelta * (float) $line->base_quantity / $ordered, 4),
        );
    }
}
