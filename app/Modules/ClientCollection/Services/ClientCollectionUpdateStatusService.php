<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\ClientCollection\Commands\UpdateStatusClientCollectionCommand;
use App\Modules\ClientCollection\Exceptions\ClientCollectionNotFoundException;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ClientCollectionUpdateStatusService
{
    public function __construct(
        private readonly ClientCollectionRepositoryInterface $repository,
        private readonly ClientCollectionPostingService $posting,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes del cobro: lo que
     * mueve son los saldos de las facturas y del cliente, y solo en los dos
     * momentos que importan —confirmarlo y anularlo ya confirmado—.
     */
    public function execute(
        string $id,
        UpdateStatusClientCollectionCommand $command,
        ?string $companyId = null,
    ): ClientCollection {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientCollectionNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $wasPosted = in_array($model->status, ['confirmed', 'completed'], true);

            if ($command->status === 'confirmed') {
                $this->posting->post($model);
            }

            /**
             * Un borrador anulado no revierte saldos —nunca llegó a abonar—,
             * pero sí libera al anticipo que lo generó, si vino de uno.
             */
            if ($command->status === 'cancelled') {
                $wasPosted ? $this->posting->reverse($model) : $this->posting->discard($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
