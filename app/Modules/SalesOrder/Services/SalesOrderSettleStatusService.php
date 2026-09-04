<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El avance del pedido lo escriben los documentos que lo cumplen, no el
 * usuario.
 *
 * `partial` y `completed` no son decisiones: son la lectura de dos cuentas que
 * ya están en las líneas. La mercancía sale con el Despacho
 * (`dispatched_quantity`) y la deuda se reconoce con la Factura de venta
 * (`invoiced_quantity`); el pedido solo se cierra cuando **las dos** llegaron a
 * lo pedido, porque un pedido entregado y sin facturar sigue teniendo algo
 * abierto con el cliente.
 *
 * Es el espejo exacto de `PurchaseOrderSettleStatusService` en compras: se
 * llama después de cada movimiento de esas cuentas y también cuando el
 * documento que las movió se anula, así que lee de cero cada vez y el estado
 * puede retroceder —de `completed` a `partial`, o de `partial` a `confirmed`—.
 *
 * `draft` y `cancelled` quedan fuera: un pedido sin confirmar no ha
 * comprometido nada y uno anulado ya no espera nada.
 */
class SalesOrderSettleStatusService
{
    /**
     * Los estados cuyo avance es un cálculo. Fuera de ellos el estado es una
     * decisión —confirmar, anular— y este servicio no la toca.
     *
     * @var array<int, string>
     */
    private const SETTLEABLE = ['confirmed', 'partial', 'completed'];

    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
    ) {}

    public function execute(string $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            $order = $this->repository->lockById($orderId);

            if ($order === null || ! in_array($order->status, self::SETTLEABLE, true)) {
                return;
            }

            $status = $this->resolve($this->repository->activeLines($order));

            if ($status === $order->status) {
                return;
            }

            $this->repository->writeFulfillmentStatus($order, $status);
        });
    }

    /**
     * El estado que le corresponde al pedido por lo que dicen sus líneas.
     *
     * Las dos cuentas no miden lo mismo. Lo **facturado** se mide contra todo
     * lo pedido: un servicio se factura igual que un tornillo. Lo **despachado**
     * se mide solo contra las líneas que llevan existencia, porque un servicio
     * o un artículo no inventariado no sale nunca en un despacho; medirlo
     * contra todo dejaría el pedido abierto para siempre. Un pedido que solo
     * vende servicios tiene ese lado cumplido de nacimiento.
     *
     * Cada cuenta se topa **línea por línea**: entregar o facturar de más en
     * una línea no puede tapar lo que falta en otra.
     *
     * @param  array<int, SalesOrderLine>  $lines
     */
    private function resolve(array $lines): string
    {
        $ordered = 0.0;
        $stocked = 0.0;
        $dispatched = 0.0;
        $invoiced = 0.0;

        foreach ($lines as $line) {
            $quantity = (float) $line->quantity;

            $ordered += $quantity;
            $invoiced += min((float) $line->invoiced_quantity, $quantity);

            if ($line->item?->movesStock() ?? true) {
                $stocked += $quantity;
                $dispatched += min((float) $line->dispatched_quantity, $quantity);
            }
        }

        $ordered = round($ordered, 4);
        $stocked = round($stocked, 4);
        $dispatched = round($dispatched, 4);
        $invoiced = round($invoiced, 4);

        /** Un pedido sin líneas vivas no tiene nada que cumplir: sigue abierto. */
        if ($ordered <= 0) {
            return 'confirmed';
        }

        /** Sin mercancía que sacar, ese lado no puede faltar. */
        $allDispatched = $stocked <= 0 || $dispatched >= $stocked;

        if ($allDispatched && $invoiced >= $ordered) {
            return 'completed';
        }

        if ($dispatched > 0 || $invoiced > 0) {
            return 'partial';
        }

        return 'confirmed';
    }
}
