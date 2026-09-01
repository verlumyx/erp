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
        private readonly TransferPostingService $posting,
    ) {}

    /**
     * Cambiar el estado no toca los importes del traslado: lo que mueve es el
     * inventario, y solo en los dos momentos que importan —confirmarlo y
     * anularlo ya confirmado—.
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
            $wasPosted = in_array($model->status, Transfer::POSTED_STATUSES, true);

            if ($command->status === 'confirmed') {
                $this->posting->post($model, $command->sentBy);
            }

            /** Un borrador anulado no revierte nada: nunca llegó a mover mercancía. */
            if ($command->status === 'cancelled' && $wasPosted) {
                $this->posting->reverse($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
