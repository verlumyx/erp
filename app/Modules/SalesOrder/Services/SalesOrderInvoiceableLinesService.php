<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\SalesOrder\Models\SalesOrderLine;

/**
 * Las líneas de un pedido de venta que todavía admiten factura.
 *
 * Lo llama la pantalla de la factura de venta en cuanto se elige el pedido: es
 * la segunda ida al servidor que el `lookup` no hace, porque el saldo por
 * facturar solo interesa del pedido que se eligió, no de los veinte que el
 * select ofrece.
 *
 * El `loadMissing` vive aquí y no en el repositorio para que el listado de
 * pedidos no pague el costo de lo que solo esta pantalla necesita.
 */
class SalesOrderInvoiceableLinesService
{
    public function __construct(
        private readonly SalesOrderFindService $findService,
    ) {}

    /**
     * @return list<SalesOrderLine>
     */
    public function execute(string $orderId, string $companyId): array
    {
        $order = $this->findService->execute($orderId, $companyId);

        $order->loadMissing(['lines.item', 'lines.measurementUnit']);

        return $order->lines
            ->where('status', 'active')
            ->filter(fn (SalesOrderLine $line): bool => $this->pendingQuantity($line) > 0)
            ->sortBy('line_number')
            ->values()
            ->all();
    }

    /** Lo pedido menos lo ya facturado. */
    private function pendingQuantity(SalesOrderLine $line): float
    {
        return round((float) $line->quantity - (float) $line->invoiced_quantity, 4);
    }
}
