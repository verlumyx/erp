<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Commands;

/**
 * La entrega ya validada y con sus cantidades resueltas por línea, lista para
 * escribirse. La arma `DispatchDeliveryService`; el repositorio no decide nada.
 */
class WriteDispatchDeliveryCommand
{
    /**
     * @param  array<string, array{delivered: float, returned: float}>  $lines
     *                                                                          Id de la línea → lo entregado y lo devuelto.
     */
    public function __construct(
        public readonly string $deliveryStatus,
        public readonly string $deliveryDate,
        public readonly array $lines = [],
        public readonly ?string $receivedByName = null,
        public readonly ?string $receivedByDocument = null,
        public readonly ?string $signaturePath = null,
        public readonly ?string $evidencePath = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $rejectionReason = null,
    ) {}
}
