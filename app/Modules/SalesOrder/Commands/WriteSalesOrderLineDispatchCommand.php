<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Commands;

/**
 * El avance de despacho de la línea ya resuelto. Lo escribe el único servicio
 * que puede moverlo, y no toca ni la cantidad pedida ni los importes: lo
 * pedido vale lo que valía, lo que cambia es cuánto de ello ya salió.
 */
class WriteSalesOrderLineDispatchCommand
{
    public function __construct(
        public readonly float $dispatchedQuantity,
        public readonly float $pendingQuantity,
        /** La reserva se libera al despachar: el stock dejó de estar comprometido. */
        public readonly float $reservedQuantity,
    ) {}
}
