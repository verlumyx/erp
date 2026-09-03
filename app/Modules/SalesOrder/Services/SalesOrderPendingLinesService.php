<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\SalesOrder\Models\SalesOrderLine;

/**
 * Las líneas de un pedido de venta que todavía deben algo.
 *
 * Un pedido se cumple por dos caminos distintos y con su propio avance cada
 * uno: la mercancía sale con los Despachos (`dispatched_quantity`) y la deuda
 * se reconoce con las Facturas de venta (`invoiced_quantity`). Qué queda
 * depende de cuál de los dos pregunte, y por eso el avance contra el que se
 * resta entra como parámetro en vez de estar fijo.
 *
 * Lo llaman esas dos pantallas en cuanto se elige el pedido: es la segunda ida
 * al servidor que el `lookup` no hace, porque el saldo solo interesa del pedido
 * que se eligió, no de los veinte que el select ofrece.
 *
 * El `loadMissing` vive aquí y no en el repositorio para que el listado de
 * pedidos no pague el costo de lo que solo estas pantallas necesitan.
 */
class SalesOrderPendingLinesService
{
    /** Lo que queda por facturar: lo pide la factura de venta. */
    public const AGAINST_INVOICED = 'invoiced_quantity';

    /** Lo que queda por despachar: lo pide el despacho. */
    public const AGAINST_DISPATCHED = 'dispatched_quantity';

    public function __construct(
        private readonly SalesOrderFindService $findService,
    ) {}

    /**
     * @param  self::AGAINST_*  $against  Avance contra el que se mide el saldo.
     * @param  string  $ids  Ids separados por coma que se devuelven aunque ya no
     *                       deban nada: son los que el documento ya tenía atados
     *                       y sin ellos su pantalla no sabría a qué apuntan.
     * @return list<SalesOrderLine>
     */
    public function execute(string $orderId, string $companyId, string $against, string $ids = ''): array
    {
        $order = $this->findService->execute($orderId, $companyId);

        $order->loadMissing(['lines.item', 'lines.measurementUnit']);

        $keep = array_filter(explode(',', $ids));

        return $order->lines
            ->where('status', 'active')
            ->filter(fn (SalesOrderLine $line): bool => $this->pendingQuantity($line, $against) > 0
                || in_array($line->id, $keep, true))
            ->sortBy('line_number')
            ->values()
            ->all();
    }

    /** Lo pedido menos lo que ese avance ya cubrió. */
    private function pendingQuantity(SalesOrderLine $line, string $against): float
    {
        return round((float) $line->quantity - (float) $line->{$against}, 4);
    }
}
