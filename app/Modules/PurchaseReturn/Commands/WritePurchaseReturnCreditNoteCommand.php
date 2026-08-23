<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Commands;

/**
 * El vínculo con la nota de crédito ya resuelto, con el estado al que deja la
 * devolución. Lo escribe el único servicio que puede moverlo, y no toca ni las
 * líneas ni los importes: la mercancía que salió es la que salió.
 */
class WritePurchaseReturnCreditNoteCommand
{
    public function __construct(
        public readonly ?string $creditNoteId,
        public readonly string $status,
    ) {}
}
