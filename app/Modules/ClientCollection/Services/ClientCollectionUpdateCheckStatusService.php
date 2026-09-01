<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\ClientCollection\Commands\UpdateCheckStatusClientCollectionCommand;
use App\Modules\ClientCollection\Commands\UpdateStatusClientCollectionCommand;
use App\Modules\ClientCollection\Exceptions\ClientCollectionNotFoundException;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El cheque tiene su propio carril: depositarlo o conformarlo no toca el estado
 * del cobro. Que rebote sí, y es el único caso: el dinero nunca entró, así que
 * el cobro se anula, sus aplicaciones se revierten y el cliente recupera la
 * deuda que este cobro le había cancelado (`docs/ventas.md` §6.3).
 *
 * Si el cheque era el del cobro espejo de un anticipo, ese anticipo vuelve a
 * borrador igual que al anularlo: el dinero nunca entró (`docs/ventas.md` §5.1).
 */
class ClientCollectionUpdateCheckStatusService
{
    public function __construct(
        private readonly ClientCollectionRepositoryInterface $repository,
        private readonly ClientCollectionPostingService $posting,
    ) {}

    public function execute(
        string $id,
        UpdateCheckStatusClientCollectionCommand $command,
        ?string $companyId = null,
    ): ClientCollection {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientCollectionNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            $this->repository->updateCheckStatus($model, $command);

            if ($command->checkStatus !== ClientCollection::CHECK_BOUNCED) {
                return;
            }

            in_array($model->status, ['confirmed', 'completed'], true)
                ? $this->posting->reverse($model)
                : $this->posting->discard($model);

            $this->repository->updateStatus($model, new UpdateStatusClientCollectionCommand(
                status: 'cancelled',
                cancellationReason: 'El cheque fue devuelto por el banco.',
            ));
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
