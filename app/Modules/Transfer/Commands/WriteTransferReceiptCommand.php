<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Commands;

/**
 * La recepción ya validada y con sus cantidades resueltas por línea, lista para
 * escribirse. La arma `TransferReceiptService`; el repositorio no decide nada.
 */
class WriteTransferReceiptCommand
{
    /**
     * @param  array<string, array{received: float, difference: float}>  $lines
     *                                                                          Id de la línea → lo recibido y lo que faltó.
     * @param  string  $status  Estado al que queda el documento: `partial` si
     *                          faltó algo, o el que ya tenía si llegó todo.
     */
    public function __construct(
        public readonly string $transferStatus,
        public readonly string $status,
        public readonly string $receivedDate,
        public readonly array $lines = [],
        public readonly ?string $receivedBy = null,
        public readonly ?string $notes = null,
    ) {}
}
