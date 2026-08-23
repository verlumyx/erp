<?php

declare(strict_types=1);

namespace App\Modules\PurchaseCreditNote\Services;

use App\Modules\PurchaseCreditNote\Models\PurchaseCreditNote;
use App\Modules\PurchaseReturn\Commands\ApplyPurchaseReturnCreditNoteCommand;
use App\Modules\PurchaseReturn\Services\PurchaseReturnApplyCreditNoteService;
use Illuminate\Support\Facades\DB;

/**
 * Mantiene al día la devolución que una nota de crédito acredita.
 *
 * `app_purchase_credit_notes.purchase_return_id` y
 * `app_purchase_returns.credit_note_id` son las dos caras del mismo vínculo
 * (`docs/compras.md` §4.1 y §7.1): en cuanto la nota apunta a una devolución,
 * la devolución queda acreditada (`completed`), y si la nota deja de apuntarle
 * —se repunta a otra o se anula— aquella vuelve a estar solo confirmada.
 *
 * Vive en el módulo de la nota porque es la nota quien decide a qué devolución
 * acredita; quien escribe en la devolución es su propio servicio.
 */
class PurchaseCreditNoteReturnSyncService
{
    public function __construct(
        private readonly PurchaseReturnApplyCreditNoteService $returns,
    ) {}

    /**
     * Apunta la nota a su devolución. `$previousReturnId` es la que tenía antes
     * de guardarla: al repuntarla, la anterior se libera.
     */
    public function sync(PurchaseCreditNote $note, ?string $previousReturnId = null): void
    {
        DB::transaction(function () use ($note, $previousReturnId): void {
            if (filled($previousReturnId) && $previousReturnId !== $note->purchase_return_id) {
                $this->apply($note, $previousReturnId, null);
            }

            if (filled($note->purchase_return_id)) {
                $this->apply($note, $note->purchase_return_id, $note->id);
            }
        });
    }

    /**
     * Suelta la devolución: la nota se anuló y el crédito que reconocía ya no
     * existe.
     */
    public function release(PurchaseCreditNote $note): void
    {
        if (blank($note->purchase_return_id)) {
            return;
        }

        $this->apply($note, $note->purchase_return_id, null);
    }

    private function apply(PurchaseCreditNote $note, string $returnId, ?string $creditNoteId): void
    {
        $this->returns->execute(new ApplyPurchaseReturnCreditNoteCommand(
            companyId: $note->company_id,
            purchaseReturnId: $returnId,
            creditNoteId: $creditNoteId,
        ));
    }
}
