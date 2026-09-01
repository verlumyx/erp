<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Services;

use App\Modules\Client\Commands\ApplyClientBalanceCommand;
use App\Modules\Client\Services\ClientApplyBalanceService;
use App\Modules\ClientAdvance\Commands\UpdateStatusClientAdvanceCommand;
use App\Modules\ClientAdvance\Exceptions\ClientAdvanceNotFoundException;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use App\Modules\ClientCollection\Models\ClientCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * El lado del anticipo de su cobro espejo.
 *
 * Lo llama el módulo de cobros en los dos únicos momentos que importan:
 * confirmar el cobro —el dinero entró, el anticipo queda recibido y el cliente
 * gana un crédito a favor— y anularlo —el anticipo vuelve a borrador para
 * corregirse y aprobarse de nuevo, conservando su `code`—.
 *
 * La transacción es anidable: llamada desde el cobro se suma a la suya como
 * savepoint.
 */
class ClientAdvanceCollectionSyncService
{
    public function __construct(
        private readonly ClientAdvanceRepositoryInterface $repository,
        private readonly ClientApplyBalanceService $clientBalances,
    ) {}

    /**
     * El cobro se confirmó: el anticipo pasa a `confirmed` y solo entonces suma
     * al crédito a favor del cliente.
     *
     * @throws ValidationException si el anticipo no estaba esperando su cobro.
     */
    public function confirm(ClientCollection $collection): ClientAdvance
    {
        return DB::transaction(function () use ($collection): ClientAdvance {
            $advance = $this->lockedAdvance($collection);

            if ($advance->status !== 'pending_confirmation') {
                throw ValidationException::withMessages([
                    'status' => "El anticipo {$advance->code} no está esperando su cobro.",
                ]);
            }

            $this->repository->updateStatus($advance, new UpdateStatusClientAdvanceCommand('confirmed'));

            $this->moveAdvanceBalance($advance, round((float) $advance->amount, 2));

            return $this->repository->findOrFail($advance->id, $advance->company_id);
        });
    }

    /**
     * El cobro se anuló —o su cheque rebotó—: el anticipo vuelve a borrador. Si
     * ya estaba recibido también se le quita al cliente el crédito que le había
     * dado.
     *
     * @throws ValidationException si el anticipo ya se aplicó a alguna factura.
     */
    public function revert(ClientCollection $collection): ?ClientAdvance
    {
        return DB::transaction(function () use ($collection): ?ClientAdvance {
            $advance = $this->lockedAdvance($collection);

            /** Un anticipo agotado o anulado ya no depende de este cobro. */
            if (! in_array($advance->status, ['pending_confirmation', 'confirmed'], true)) {
                return null;
            }

            if (round((float) $advance->applied_amount, 2) > 0) {
                throw ValidationException::withMessages([
                    'status' => "El anticipo {$advance->code} ya se aplicó a facturas: revierte esas aplicaciones primero.",
                ]);
            }

            $wasReceived = $advance->status === 'confirmed';

            $this->repository->updateStatus($advance, new UpdateStatusClientAdvanceCommand('draft'));

            if ($wasReceived) {
                $this->moveAdvanceBalance($advance, -round((float) $advance->amount, 2));
            }

            return $this->repository->findOrFail($advance->id, $advance->company_id);
        });
    }

    private function lockedAdvance(ClientCollection $collection): ClientAdvance
    {
        $advance = $this->repository->lockById((string) $collection->origin_id, $collection->company_id);

        if ($advance === null) {
            throw new ClientAdvanceNotFoundException;
        }

        return $advance;
    }

    /** El crédito a favor del cliente lo mueve siempre su propio servicio. */
    private function moveAdvanceBalance(ClientAdvance $advance, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        $this->clientBalances->execute(new ApplyClientBalanceCommand(
            companyId: $advance->company_id,
            clientId: $advance->client_id,
            advanceBalanceDelta: $delta,
        ));
    }
}
