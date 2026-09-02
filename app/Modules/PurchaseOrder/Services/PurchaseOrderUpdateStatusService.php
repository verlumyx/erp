<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\Commands\UpdateStatusPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Exceptions\PurchaseOrderNotFoundException;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PurchaseOrderUpdateStatusService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
        private readonly PurchaseOrderIncomingService $incoming,
        private readonly PurchaseOrderMirrorEntryService $mirror,
    ) {}

    /**
     * Confirmar la orden no mete nada en la bodega, pero sí anuncia la
     * mercancía como en camino y deja escrita la entrada que la recibirá;
     * anularla borra lo uno y lo otro. Son los dos únicos momentos en los que
     * el estado toca la existencia (`docs/compras.md` §2.2).
     */
    public function execute(
        string $id,
        UpdateStatusPurchaseOrderCommand $command,
        ?string $companyId = null,
    ): PurchaseOrder {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseOrderNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasAnnounced = $model->status !== 'draft';

            /**
             * Se comprueba antes de tocar nada: si la mercancía ya entró, la
             * orden no se anula y la transacción no debe llegar a moverla.
             */
            if ($command->status === 'cancelled') {
                $this->mirror->guardCancellable($model);
            }

            if ($command->status === 'confirmed') {
                $this->incoming->announce($model);
                $this->mirror->create($model);
            }

            if ($command->status === 'cancelled') {
                /** Un borrador anulado no borra nada: nunca llegó a anunciar. */
                if ($wasAnnounced) {
                    $this->incoming->withdraw($model);
                }

                $this->mirror->cancel($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
