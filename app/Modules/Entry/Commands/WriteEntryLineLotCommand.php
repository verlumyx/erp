<?php

declare(strict_types=1);

namespace App\Modules\Entry\Commands;

/**
 * El lote de una fila de trazabilidad ya resuelto, con el número con el que
 * quedó registrado.
 *
 * Lo escribe el único servicio que puede moverlo —`EntryTraceabilityService`,
 * al confirmar— y no toca ni las cantidades ni los importes: lo que llegó es lo
 * que llegó, lo que cambia es que ya se sabe a qué lote pertenece.
 */
class WriteEntryLineLotCommand
{
    public function __construct(
        public readonly ?string $lotId,
        public readonly ?string $lotNumber,
    ) {}
}
