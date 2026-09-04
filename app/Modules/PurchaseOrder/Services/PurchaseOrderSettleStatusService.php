<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El avance de la orden lo escriben los documentos que la cumplen, no el
 * usuario.
 *
 * `partial` y `completed` no son decisiones: son la lectura de dos cuentas que
 * ya están en las líneas. La mercancía la trae la Entrada (`received_quantity`)
 * y la deuda la reconoce la Factura de compra (`invoiced_quantity`); la orden
 * solo se cierra cuando **las dos** llegaron a lo pedido, porque un pedido
 * recibido y sin facturar sigue teniendo algo abierto con el proveedor.
 *
 * Se llama después de cada movimiento de esas cuentas, y también cuando el
 * documento que las movió se anula: por eso lee de cero cada vez y el estado
 * puede retroceder —de `completed` a `partial`, o de `partial` a `confirmed`—
 * si lo recibido o lo facturado se deshace.
 *
 * `draft` y `cancelled` quedan fuera: una orden sin confirmar no ha pedido nada
 * y una anulada ya no espera nada.
 */
class PurchaseOrderSettleStatusService
{
    /**
     * Los estados cuyo avance es un cálculo. Fuera de ellos el estado es una
     * decisión —confirmar, anular— y este servicio no la toca.
     *
     * @var array<int, string>
     */
    private const SETTLEABLE = ['confirmed', 'partial', 'completed'];

    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
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
     * El estado que le corresponde a la orden por lo que dicen sus líneas.
     *
     * Las dos cuentas no miden lo mismo. Lo **facturado** se mide contra todo
     * lo pedido: un servicio se factura igual que un tornillo. Lo **recibido**
     * se mide solo contra las líneas que llevan existencia, porque un servicio
     * o un artículo no inventariado no entra nunca por una entrada; medirlo
     * contra todo dejaría la orden abierta para siempre. Una orden que solo
     * pide servicios tiene ese lado cumplido de nacimiento.
     *
     * Cada cuenta se topa **línea por línea**: al proveedor se le admite
     * despachar o facturar de más, pero ese exceso es de esa línea y no puede
     * tapar lo que falta en otra.
     *
     * @param  array<int, PurchaseOrderLine>  $lines
     */
    private function resolve(array $lines): string
    {
        $ordered = 0.0;
        $stocked = 0.0;
        $received = 0.0;
        $invoiced = 0.0;

        foreach ($lines as $line) {
            $quantity = (float) $line->quantity;

            $ordered += $quantity;
            $invoiced += min((float) $line->invoiced_quantity, $quantity);

            if ($line->item?->movesStock() ?? true) {
                $stocked += $quantity;
                $received += min((float) $line->received_quantity, $quantity);
            }
        }

        $ordered = round($ordered, 4);
        $stocked = round($stocked, 4);
        $received = round($received, 4);
        $invoiced = round($invoiced, 4);

        /** Una orden sin líneas vivas no tiene nada que cumplir: sigue abierta. */
        if ($ordered <= 0) {
            return 'confirmed';
        }

        /** Sin mercancía que esperar, ese lado no puede faltar. */
        $allReceived = $stocked <= 0 || $received >= $stocked;

        if ($allReceived && $invoiced >= $ordered) {
            return 'completed';
        }

        if ($received > 0 || $invoiced > 0) {
            return 'partial';
        }

        return 'confirmed';
    }
}
