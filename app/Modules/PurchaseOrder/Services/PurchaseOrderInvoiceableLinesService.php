<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;

/**
 * Las líneas de una orden de compra que todavía admiten factura.
 *
 * Lo llama la pantalla de la factura de compra en cuanto se elige la orden: es
 * la segunda ida al servidor que el `lookup` no hace, porque el saldo por
 * facturar solo interesa de la orden que se eligió, no de las veinte que el
 * select ofrece.
 *
 * El `loadMissing` vive aquí y no en el repositorio para que el listado de
 * órdenes no pague el costo de lo que solo esta pantalla necesita.
 */
class PurchaseOrderInvoiceableLinesService
{
    public function __construct(
        private readonly PurchaseOrderFindService $findService,
    ) {}

    /**
     * @return list<PurchaseOrderLine>
     */
    public function execute(string $orderId, string $companyId): array
    {
        $order = $this->findService->execute($orderId, $companyId);

        $order->loadMissing(['lines.item', 'lines.measurementUnit']);

        return $order->lines
            ->where('status', 'active')
            ->filter(fn (PurchaseOrderLine $line): bool => $this->pendingQuantity($line) > 0)
            ->sortBy('line_number')
            ->values()
            ->all();
    }

    /** Lo pedido menos lo ya facturado. */
    private function pendingQuantity(PurchaseOrderLine $line): float
    {
        return round((float) $line->quantity - (float) $line->invoiced_quantity, 4);
    }
}
