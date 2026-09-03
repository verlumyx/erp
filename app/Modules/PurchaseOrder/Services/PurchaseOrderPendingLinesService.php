<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\Item\Models\Item;
use App\Modules\PurchaseOrder\Models\PurchaseOrderLine;

/**
 * Las líneas de una orden de compra que todavía deben algo.
 *
 * Una orden se cumple por dos caminos distintos y con su propio avance cada
 * uno: la mercancía llega con las Entradas (`received_quantity`) y la deuda se
 * reconoce con las Facturas de compra (`invoiced_quantity`). Qué queda depende
 * de cuál de los dos pregunte, y por eso el avance contra el que se resta entra
 * como parámetro en vez de estar fijo.
 *
 * Lo llaman esas dos pantallas en cuanto se elige la orden: es la segunda ida
 * al servidor que el `lookup` no hace, porque el saldo solo interesa de la
 * orden que se eligió, no de las veinte que el select ofrece.
 *
 * El `loadMissing` vive aquí y no en el repositorio para que el listado de
 * órdenes no pague el costo de lo que solo estas pantallas necesitan.
 */
class PurchaseOrderPendingLinesService
{
    /** Lo que queda por facturar: lo pide la factura de compra. */
    public const AGAINST_INVOICED = 'invoiced_quantity';

    /** Lo que queda por recibir: lo pide la entrada de mercancía. */
    public const AGAINST_RECEIVED = 'received_quantity';


    public function __construct(
        private readonly PurchaseOrderFindService $findService,
    ) {}

    /**
     * @param  self::AGAINST_*  $against  Avance contra el que se mide el saldo.
     * @param  string  $ids  Ids separados por coma que se devuelven aunque ya no
     *                       deban nada: son los que el documento ya tenía atados
     *                       y sin ellos su pantalla no sabría a qué apuntan.
     * @return list<PurchaseOrderLine>
     */
    public function execute(string $orderId, string $companyId, string $against, string $ids = ''): array
    {
        $order = $this->findService->execute($orderId, $companyId);

        $order->loadMissing(['lines.item', 'lines.measurementUnit']);

        $keep = array_filter(explode(',', $ids));

        return $order->lines
            ->where('status', 'active')
            ->filter(fn (PurchaseOrderLine $line): bool => $this->pendingQuantity($line, $against) > 0
                || in_array($line->id, $keep, true))
            ->sortBy('line_number')
            ->values()
            ->all();
    }

    /**
     * Lo pedido menos lo que ese avance ya cubrió.
     *
     * Un artículo sin existencia llega nunca: por el lado de la mercancía su línea
     * no debe nada, aunque por el de la factura siga debiendo.
     */
    private function pendingQuantity(PurchaseOrderLine $line, string $against): float
    {
        if ($against === self::AGAINST_RECEIVED && ! $this->movesStock($line)) {
            return 0.0;
        }

        return round((float) $line->quantity - (float) $line->{$against}, 4);
    }

    /** Si el artículo de la línea lleva existencia. */
    private function movesStock(PurchaseOrderLine $line): bool
    {
        $item = $line->item;

        return ! ($item instanceof Item) || $item->movesStock();
    }
}
