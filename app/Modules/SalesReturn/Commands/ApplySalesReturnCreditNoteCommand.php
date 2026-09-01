<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Commands;

/**
 * La nota de crédito que acredita una devolución, o su retirada.
 *
 * No la construye un Request: la arma el módulo de notas de crédito cuando una
 * nota nace apuntando a una devolución, cuando deja de apuntarle o cuando se
 * anula. `creditNoteId` nulo es exactamente eso: la devolución vuelve a estar
 * sin acreditar.
 */
class ApplySalesReturnCreditNoteCommand
{
    public function __construct(
        public readonly ?string $companyId,
        public readonly string $salesReturnId,
        public readonly ?string $creditNoteId,
    ) {}
}
