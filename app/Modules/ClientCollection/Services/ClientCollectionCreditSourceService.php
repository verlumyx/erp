<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\ClientAdvance\Commands\UpdateStatusClientAdvanceCommand;
use App\Modules\ClientAdvance\Commands\WriteClientAdvanceAppliedCommand;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\SalesCreditNote\Commands\UpdateStatusSalesCreditNoteCommand;
use App\Modules\SalesCreditNote\Commands\WriteSalesCreditNoteAppliedCommand;
use App\Modules\SalesCreditNote\Models\SalesCreditNote;
use App\Modules\SalesCreditNote\Repositories\Contracts\SalesCreditNoteRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El crédito que respalda un cobro que no trae dinero.
 *
 * Cobrar con `advance` o con `credit_note` no es recibir: es gastar un saldo a
 * favor que el cliente ya tiene. Este servicio es el que comprueba que ese
 * crédito exista, sea del mismo cliente, esté confirmado y alcance, y el que lo
 * consume —o lo devuelve— cuando el cobro se confirma o se anula.
 *
 * El anticipo consume además el `advance_balance` del cliente; la nota de
 * crédito no, porque bajó su `current_balance` cuando se confirmó y nunca fue
 * crédito a favor.
 */
class ClientCollectionCreditSourceService
{
    public function __construct(
        private readonly ClientAdvanceRepositoryInterface $advances,
        private readonly SalesCreditNoteRepositoryInterface $creditNotes,
        private readonly ClientCollectionApplyCreditService $credits,
    ) {}

    /**
     * El crédito indicado sirve para este cobro y alcanza para lo que se quiere
     * repartir. `$exceptApplied` es lo que este mismo cobro ya tenía tomado del
     * crédito, que no compite consigo mismo al editarse.
     *
     * @throws ValidationException
     */
    public function guard(
        string $paymentMethod,
        ?string $creditSourceId,
        string $clientId,
        ?string $companyId,
        float $applied,
        float $exceptApplied = 0,
    ): void {
        if (! isset(ClientCollection::CREDIT_METHODS[$paymentMethod])) {
            if (filled($creditSourceId)) {
                throw ValidationException::withMessages([
                    'credit_source_id' => 'Un cobro con dinero de por medio no gasta ningún crédito.',
                ]);
            }

            return;
        }

        if (blank($creditSourceId)) {
            throw ValidationException::withMessages([
                'credit_source_id' => 'Indica de qué anticipo o nota de crédito sale el cobro.',
            ]);
        }

        $source = $this->source($paymentMethod, (string) $creditSourceId, $companyId);

        if ($source === null) {
            throw ValidationException::withMessages([
                'credit_source_id' => 'El crédito indicado no existe en esta empresa.',
            ]);
        }

        if ($source->client_id !== $clientId) {
            throw ValidationException::withMessages([
                'credit_source_id' => 'El crédito pertenece a otro cliente: un cobro no cruza clientes.',
            ]);
        }

        if (! in_array($source->status, $this->applicableStatuses($paymentMethod), true)) {
            throw ValidationException::withMessages([
                'credit_source_id' => "El crédito {$source->code} no está disponible en su estado actual.",
            ]);
        }

        $available = round((float) $source->balance + $exceptApplied, 2);

        if (round($applied, 2) > $available) {
            throw ValidationException::withMessages([
                'credit_source_id' => "El crédito {$source->code} solo tiene {$available} disponible.",
            ]);
        }
    }

    /** Gasta el crédito del cobro que se acaba de confirmar. */
    public function consume(ClientCollection $collection, float $applied): void
    {
        $this->move($collection, round($applied, 2));
    }

    /** Devuelve el crédito del cobro que se acaba de anular. */
    public function release(ClientCollection $collection, float $applied): void
    {
        $this->move($collection, -round($applied, 2));
    }

    /**
     * Mueve lo aplicado del crédito y, si es un anticipo, el saldo a favor del
     * cliente. Un cobro con dinero de por medio no pasa por aquí.
     */
    private function move(ClientCollection $collection, float $delta): void
    {
        if (! $collection->fundedByCredit() || $delta === 0.0) {
            return;
        }

        DB::transaction(function () use ($collection, $delta): void {
            $source = $this->lockedSource($collection);

            if ($source === null) {
                return;
            }

            $applied = max(round((float) $source->applied_amount + $delta, 2), 0);

            $source instanceof ClientAdvance
                ? $this->writeAdvance($source, $applied)
                : $this->writeCreditNote($source, $applied);

            /** El anticipo era saldo a favor del cliente: gastarlo lo consume. */
            if ($source instanceof ClientAdvance) {
                $this->credits->moveAdvanceBalance($collection->company_id, $collection->client_id, -$delta);
            }
        });
    }

    /** @return ClientAdvance|SalesCreditNote|null */
    private function source(string $paymentMethod, string $id, ?string $companyId): ?Model
    {
        return $paymentMethod === 'advance'
            ? $this->advances->findById($id, $companyId)
            : $this->creditNotes->findById($id, $companyId);
    }

    /** @return ClientAdvance|SalesCreditNote|null */
    private function lockedSource(ClientCollection $collection): ?Model
    {
        $id = (string) $collection->credit_source_id;

        return $collection->payment_method === 'advance'
            ? $this->advances->lockById($id, $collection->company_id)
            : $this->creditNotes->lockById($id, $collection->company_id);
    }

    /**
     * Desde qué estados se puede gastar el crédito. Un anticipo a medio gastar
     * sigue sirviendo; una nota solo desde confirmada, porque aplicarla entera
     * la agota y la deja `completed`.
     *
     * @return array<int, string>
     */
    private function applicableStatuses(string $paymentMethod): array
    {
        return $paymentMethod === 'advance'
            ? ['confirmed', 'partial']
            : ['confirmed'];
    }

    /**
     * Un anticipo a medio gastar está `partial`; agotado, `completed`; devuelto
     * entero vuelve a `confirmed`.
     */
    private function writeAdvance(ClientAdvance $advance, float $applied): void
    {
        $amount = round((float) $advance->amount, 2);
        $balance = round($amount - $applied - (float) $advance->refunded_amount, 2);

        $this->advances->writeApplied($advance, new WriteClientAdvanceAppliedCommand(
            appliedAmount: $applied,
            balance: $balance,
        ));

        $status = match (true) {
            $balance <= 0 => 'completed',
            $applied > 0 => 'partial',
            default => 'confirmed',
        };

        if ($status !== $advance->status) {
            $this->advances->updateStatus($advance, new UpdateStatusClientAdvanceCommand($status));
        }
    }

    /** Una nota agotada está `completed`; devuelta entera vuelve a `confirmed`. */
    private function writeCreditNote(SalesCreditNote $note, float $applied): void
    {
        $total = round((float) $note->total, 2);
        $balance = round($total - $applied, 2);

        $this->creditNotes->writeApplied($note, new WriteSalesCreditNoteAppliedCommand(
            appliedAmount: $applied,
            balance: $balance,
        ));

        $status = $balance <= 0 ? 'completed' : 'confirmed';

        if ($status !== $note->status) {
            $this->creditNotes->updateStatus($note, new UpdateStatusSalesCreditNoteCommand($status));
        }
    }
}
