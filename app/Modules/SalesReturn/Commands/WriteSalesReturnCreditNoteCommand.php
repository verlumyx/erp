<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Commands;

/**
 * El vínculo con la nota de crédito ya resuelto, con el estado al que deja la
 * devolución. Lo escribe el único servicio que puede moverlo, y no toca ni las
 * líneas ni los importes: la mercancía que volvió es la que volvió.
 */
class WriteSalesReturnCreditNoteCommand
{
    public function __construct(
        public readonly ?string $creditNoteId,
        public readonly string $status,
    ) {}
}
