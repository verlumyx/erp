<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\PurchaseReturn\Commands\ApplyPurchaseReturnCreditNoteCommand;
use App\Modules\PurchaseReturn\Commands\WritePurchaseReturnCreditNoteCommand;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El único camino que mueve `credit_note_id` de una devolución.
 *
 * Lo llama el módulo de notas de crédito: una nota que apunta a una devolución
 * la deja acreditada (`completed`), y retirarle esa nota —porque se anuló o
 * porque pasó a apuntar a otra— la devuelve a `confirmed`, con su mercancía
 * fuera pero sin acreditar.
 *
 * Una devolución no admite dos notas vivas: el crédito del proveedor se
 * duplicaría. La transacción es anidable: llamado desde la nota que acredita se
 * suma a la abierta como savepoint.
 */
class PurchaseReturnApplyCreditNoteService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repository,
    ) {}

    public function execute(ApplyPurchaseReturnCreditNoteCommand $command): ?PurchaseReturn
    {
        return DB::transaction(function () use ($command): ?PurchaseReturn {
            $return = $this->repository->lockById($command->purchaseReturnId, $command->companyId);

            /**
             * Una devolución que no existe en esta empresa no es un error del
             * módulo que acredita: el Request de la nota ya la validó, y aquí
             * simplemente no hay nada que marcar.
             */
            if ($return === null) {
                return null;
            }

            if ($command->creditNoteId === null) {
                return $this->detach($return);
            }

            return $this->attach($return, $command->creditNoteId);
        });
    }

    /**
     * Marca la devolución como acreditada. Solo tiene sentido sobre una cuya
     * mercancía ya salió: acreditar un borrador sería reconocer un crédito por
     * algo que el proveedor todavía no ha recibido.
     */
    private function attach(PurchaseReturn $return, string $creditNoteId): PurchaseReturn
    {
        if ($return->credit_note_id === $creditNoteId) {
            return $return;
        }

        if (filled($return->credit_note_id)) {
            throw ValidationException::withMessages([
                'purchase_return_id' => 'Esa devolución ya está acreditada con otra nota de crédito.',
            ]);
        }

        if (! in_array($return->status, PurchaseReturn::POSTED_STATUSES, true)) {
            throw ValidationException::withMessages([
                'purchase_return_id' => 'Confirma la devolución antes de acreditarla: la mercancía todavía no ha salido.',
            ]);
        }

        return $this->repository->writeCreditNote($return, new WritePurchaseReturnCreditNoteCommand(
            creditNoteId: $creditNoteId,
            status: 'completed',
        ));
    }

    /**
     * Le retira la nota. La devolución vuelve a `confirmed`, que es donde
     * estaba antes de acreditarse; una ya anulada se queda como está, porque su
     * mercancía volvió al inventario y no hay a qué regresar.
     */
    private function detach(PurchaseReturn $return): PurchaseReturn
    {
        if (blank($return->credit_note_id)) {
            return $return;
        }

        return $this->repository->writeCreditNote($return, new WritePurchaseReturnCreditNoteCommand(
            creditNoteId: null,
            status: $return->status === 'cancelled' ? 'cancelled' : 'confirmed',
        ));
    }
}
