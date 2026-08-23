<?php

declare(strict_types=1);

namespace App\Modules\InventoryMovement\Commands;

/**
 * Un asiento del kardex, en unidad base.
 *
 * No lo construye un Request: el kardex no se captura. Lo arma el documento
 * que mueve el inventario —factura, despacho, traslado, entrada, ajuste—
 * dentro de su propia transacción.
 *
 * `quantity` es **siempre positiva**: la dirección la pone `type`.
 */
class RegisterInventoryMovementCommand
{
    public function __construct(
        public readonly string $companyId,
        public readonly string $itemId,
        public readonly string $warehouseId,
        public readonly string $locationId,
        public readonly string $type,
        public readonly string $originType,
        public readonly string $originId,
        public readonly float $quantity,
        /**
         * Costo unitario de la entrada. En una salida se deja nulo y el
         * movimiento se valora al promedio vigente de la existencia.
         */
        public readonly ?float $unitCost = null,
        public readonly ?string $movementDate = null,
        public readonly ?string $originLineId = null,
        public readonly ?string $lotId = null,
        public readonly ?string $serialId = null,
        public readonly ?string $reversalOfId = null,
        public readonly ?string $notes = null,
        public readonly ?string $createdBy = null,
    ) {}
}
