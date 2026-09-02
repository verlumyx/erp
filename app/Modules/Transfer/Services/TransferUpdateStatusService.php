<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Transfer\Commands\UpdateStatusTransferCommand;
use App\Modules\Transfer\Exceptions\TransferNotFoundException;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TransferUpdateStatusService
{
    public function __construct(
        private readonly TransferRepositoryInterface $repository,
        private readonly TransferMirrorDispatchService $mirror,
    ) {}

    /**
     * El traslado no mueve inventario: lo mueven el despacho que saca la
     * mercancía del origen y la entrada que la mete en el destino. Confirmarlo
     * escribe ese despacho en borrador; anularlo lo borra.
     */
    public function execute(
        string $id,
        UpdateStatusTransferCommand $command,
        ?string $companyId = null,
    ): Transfer {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new TransferNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            /**
             * Se comprueba antes de tocar nada: si la mercancía ya salió, el
             * traslado no se anula hasta anular el despacho que la sacó.
             */
            if ($command->status === 'cancelled') {
                $this->mirror->guardCancellable($model);
            }

            if ($command->status === 'confirmed') {
                $this->mirror->create($model);
            }

            if ($command->status === 'cancelled') {
                $this->mirror->cancel($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
