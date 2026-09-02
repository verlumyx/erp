<?php

declare(strict_types=1);

namespace App\Modules\Entry\Commands;

/**
 * La serie de una fila de trazabilidad ya resuelta contra el maestro.
 *
 * Igual que el lote, la escribe `EntryTraceabilityService` al confirmar: hasta
 * entonces la fila solo conoce el número impreso en la unidad.
 */
class WriteEntryLineSerialCommand
{
    public function __construct(
        public readonly ?string $serialId,
        public readonly ?string $serialNumber,
    ) {}
}
