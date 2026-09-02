<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\SalesOrder\Commands\UpdateStatusSalesOrderCommand;
use App\Modules\SalesOrder\Exceptions\SalesOrderNotFoundException;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SalesOrderUpdateStatusService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
        private readonly SalesOrderReservationService $reservations,
        private readonly SalesOrderCreditService $credit,
        private readonly SalesOrderMirrorDispatchService $mirror,
    ) {}

    /**
     * Confirmar el pedido comprueba el crédito del cliente, compromete la
     * mercancía —no la descarga— y deja escrito el despacho que la sacará;
     * anularlo suelta lo uno y borra lo otro. Son los dos únicos momentos en
     * los que el estado toca la existencia (`docs/ventas.md` §2.2).
     */
    public function execute(string $id, UpdateStatusSalesOrderCommand $command, ?string $companyId = null): SalesOrder
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesOrderNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasReserved = $model->status !== 'draft';

            /**
             * Se comprueba antes de tocar nada: si la mercancía ya salió, el
             * pedido no se anula y la transacción no debe llegar a moverla.
             */
            if ($command->status === 'cancelled') {
                $this->mirror->guardCancellable($model);
            }

            if ($command->status === 'confirmed') {
                $this->credit->guard($model, $command->allowsCreditOverride);
                $this->reservations->reserve($model);
                $this->mirror->create($model);
            }

            if ($command->status === 'cancelled') {
                /** Un borrador anulado no libera nada: nunca llegó a comprometer. */
                if ($wasReserved) {
                    $this->reservations->release($model);
                }

                $this->mirror->cancel($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
