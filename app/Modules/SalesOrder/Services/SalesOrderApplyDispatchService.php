<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\SalesOrder\Commands\ApplySalesOrderDispatchCommand;
use App\Modules\SalesOrder\Commands\WriteSalesOrderLineDispatchCommand;
use App\Modules\SalesOrder\Exceptions\SalesOrderLineNotFoundException;
use App\Modules\SalesOrder\Exceptions\SalesOrderOverDispatchedException;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que mueve `dispatched_quantity` de una línea de pedido.
 *
 * Lo llama el despacho al confirmarse, y otra vez con el signo contrario cuando
 * ese despacho se anula o cuando el cliente devuelve parte de lo que le
 * llevaron. Lo despachado no puede pasarse de lo pedido ni bajar de cero.
 *
 * Despachar **libera la reserva**: lo que salió de la bodega dejó de estar
 * comprometido, porque ya no está. La reserva no se rehace al deshacer el
 * despacho: la mercancía vuelve a la bodega como existencia libre y es el
 * pedido el que decide si vuelve a comprometerla.
 *
 * La transacción es anidable: llamado desde el documento que despacha se suma a
 * la transacción abierta como savepoint.
 */
class SalesOrderApplyDispatchService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
        private readonly SalesOrderReservationService $reservations,
        private readonly SalesOrderSettleStatusService $settle,
    ) {}

    public function execute(ApplySalesOrderDispatchCommand $command): SalesOrderLine
    {
        return DB::transaction(function () use ($command): SalesOrderLine {
            $line = $this->repository->lockLineById($command->salesOrderLineId, $command->companyId);

            if ($line === null) {
                throw new SalesOrderLineNotFoundException;
            }

            $ordered = round((float) $line->quantity, 4);
            $dispatched = round((float) $line->dispatched_quantity + $command->dispatchedDelta, 4);

            if ($dispatched < 0 || $dispatched > $ordered) {
                throw new SalesOrderOverDispatchedException;
            }

            /** Solo la salida libera reserva; el reingreso no vuelve a comprometerla. */
            $released = max(0.0, $command->dispatchedDelta);
            $reserved = max(0.0, round((float) $line->reserved_quantity - $released, 4));

            $written = $this->repository->writeLineDispatch(
                $line,
                new WriteSalesOrderLineDispatchCommand(
                    dispatchedQuantity: $dispatched,
                    pendingQuantity: round($ordered - $dispatched, 4),
                    reservedQuantity: $reserved,
                ),
            );

            /**
             * Y la existencia deja de tenerlo comprometido: la salida ya lo
             * descontó del saldo, así que mantener la reserva lo restaría dos
             * veces de lo disponible.
             */
            $this->releaseStock($written, $released);

            $this->repository->refreshDispatchedPercent($written->sales_order_id);
            $this->settle->execute($written->sales_order_id);

            return $written;
        });
    }
    /**
     * Suelta en la bodega del pedido lo que la salida acaba de llevarse, en
     * unidad base. Una línea sin pedido detrás no reserva nada, así que
     * tampoco suelta nada.
     */
    private function releaseStock(SalesOrderLine $line, float $released): void
    {
        if ($released <= 0.0) {
            return;
        }

        $order = $line->salesOrder;

        if ($order === null) {
            return;
        }

        $ordered = round((float) $line->quantity, 4);

        $this->reservations->moveStockReservation(
            $order->company_id,
            $order->warehouse_id,
            $line->item_id,
            $ordered > 0 ? -round($released * (float) $line->base_quantity / $ordered, 4) : 0.0,
        );
    }
}
