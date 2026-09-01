<?php

declare(strict_types=1);

namespace App\Modules\SalesCreditNote\Services;

use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesReturn\Commands\ApplySalesReturnCreditNoteCommand;
use App\Modules\SalesReturn\Services\SalesReturnApplyCreditNoteService;
use Illuminate\Support\Facades\DB;

/**
 * Mantiene al día la devolución que una nota de crédito acredita.
 *
 * `app_sales_credit_notes.sales_return_id` y `app_sales_returns.credit_note_id`
 * son las dos caras del mismo vínculo (`docs/ventas.md` §5.1 y §7.1): en cuanto
 * la nota apunta a una devolución, la devolución queda acreditada
 * (`completed`), y si la nota deja de apuntarle —se repunta a otra o se anula—
 * aquella vuelve a estar solo confirmada.
 *
 * Vive en el módulo de la nota porque es la nota quien decide a qué devolución
 * acredita; quien escribe en la devolución es su propio servicio.
 */
class SalesCreditNoteReturnSyncService
{
    public function __construct(
        private readonly SalesReturnApplyCreditNoteService $returns,
    ) {}

    /**
     * Apunta la nota a su devolución. `$previousReturnId` es la que tenía antes
     * de guardarla: al repuntarla, la anterior se libera.
     */
    public function sync(SalesCreditNote $note, ?string $previousReturnId = null): void
    {
        DB::transaction(function () use ($note, $previousReturnId): void {
            if (filled($previousReturnId) && $previousReturnId !== $note->sales_return_id) {
                $this->apply($note, $previousReturnId, null);
            }

            if (filled($note->sales_return_id)) {
                $this->apply($note, $note->sales_return_id, $note->id);
            }
        });
    }

    /**
     * Suelta la devolución: la nota se anuló y el crédito que reconocía ya no
     * existe.
     */
    public function release(SalesCreditNote $note): void
    {
        if (blank($note->sales_return_id)) {
            return;
        }

        $this->apply($note, $note->sales_return_id, null);
    }

    private function apply(SalesCreditNote $note, string $returnId, ?string $creditNoteId): void
    {
        $this->returns->execute(new ApplySalesReturnCreditNoteCommand(
            companyId: $note->company_id,
            salesReturnId: $returnId,
            creditNoteId: $creditNoteId,
        ));
    }
}
