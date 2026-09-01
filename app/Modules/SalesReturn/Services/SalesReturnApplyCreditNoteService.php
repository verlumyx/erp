<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\SalesReturn\Commands\ApplySalesReturnCreditNoteCommand;
use App\Modules\SalesReturn\Commands\WriteSalesReturnCreditNoteCommand;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El único camino que mueve `credit_note_id` de una devolución.
 *
 * Lo llama el módulo de notas de crédito: una nota que apunta a una devolución
 * la deja acreditada (`completed`), y retirarle esa nota —porque se anuló o
 * porque pasó a apuntar a otra— la devuelve a `confirmed`, con su mercancía ya
 * reingresada pero sin acreditar.
 *
 * Una devolución no admite dos notas vivas: el crédito al cliente se
 * duplicaría. La transacción es anidable: llamado desde la nota que acredita se
 * suma a la abierta como savepoint.
 */
class SalesReturnApplyCreditNoteService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $repository,
    ) {}

    public function execute(ApplySalesReturnCreditNoteCommand $command): ?SalesReturn
    {
        return DB::transaction(function () use ($command): ?SalesReturn {
            $return = $this->repository->lockById($command->salesReturnId, $command->companyId);

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
     * mercancía ya volvió: acreditar un borrador sería reconocer un crédito por
     * algo que todavía no se ha recibido.
     */
    private function attach(SalesReturn $return, string $creditNoteId): SalesReturn
    {
        if ($return->credit_note_id === $creditNoteId) {
            return $return;
        }

        if (filled($return->credit_note_id)) {
            throw ValidationException::withMessages([
                'sales_return_id' => 'Esa devolución ya está acreditada con otra nota de crédito.',
            ]);
        }

        if (! in_array($return->status, SalesReturn::POSTED_STATUSES, true)) {
            throw ValidationException::withMessages([
                'sales_return_id' => 'Confirma la devolución antes de acreditarla: la mercancía todavía no ha vuelto.',
            ]);
        }

        return $this->repository->writeCreditNote($return, new WriteSalesReturnCreditNoteCommand(
            creditNoteId: $creditNoteId,
            status: 'completed',
        ));
    }

    /**
     * Le retira la nota. La devolución vuelve a `confirmed`, que es donde
     * estaba antes de acreditarse; una ya anulada se queda como está, porque su
     * mercancía volvió a salir del inventario y no hay a qué regresar.
     */
    private function detach(SalesReturn $return): SalesReturn
    {
        if (blank($return->credit_note_id)) {
            return $return;
        }

        return $this->repository->writeCreditNote($return, new WriteSalesReturnCreditNoteCommand(
            creditNoteId: null,
            status: $return->status === 'cancelled' ? 'cancelled' : 'confirmed',
        ));
    }
}
