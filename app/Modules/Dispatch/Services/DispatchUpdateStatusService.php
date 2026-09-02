<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Exceptions\DispatchNotFoundException;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use Illuminate\Support\Facades\DB;

class DispatchUpdateStatusService
{
    public function __construct(
        private readonly DispatchRepositoryInterface $repository,
        private readonly DispatchPostingService $posting,
        private readonly DispatchMirrorEntryService $mirror,
    ) {}

    /**
     * Cambiar el estado no toca los importes del despacho: lo que mueve es el
     * inventario y el avance del pedido, y solo en los dos momentos que
     * importan —confirmarlo y anularlo ya confirmado—.
     */
    public function execute(
        string $id,
        UpdateStatusDispatchCommand $command,
        ?string $companyId = null,
    ): Dispatch {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new DispatchNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = in_array($model->status, Dispatch::POSTED_STATUSES, true);

            /**
             * Se comprueba antes de tocar nada: si la mercancía ya entró en el
             * destino, el despacho no se anula hasta anular esa entrada.
             */
            if ($command->status === 'cancelled') {
                $this->mirror->guardCancellable($model);
            }

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
                /** Si sirve un traslado, deja escrita la entrada que lo recibe. */
                $this->mirror->create($model);
            }

            if ($command->status === 'cancelled') {
                /** Un borrador anulado no revierte nada: nunca llegó a sacar mercancía. */
                if ($wasPosted) {
                    $this->posting->reverse($model);
                }

                $this->mirror->cancel($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
