<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Services;

use App\Modules\ClientAdvance\Commands\UpdateStatusClientAdvanceCommand;
use App\Modules\ClientAdvance\Exceptions\ClientAdvanceNotFoundException;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ClientAdvanceUpdateStatusService
{
    public function __construct(
        private readonly ClientAdvanceRepositoryInterface $repository,
        private readonly ClientAdvanceMirrorCollectionService $mirror,
    ) {}

    /**
     * Cambiar el estado no toca las tasas ni los importes del anticipo: lo que
     * mueve es su cobro espejo. Aprobarlo lo crea; anular el anticipo lo anula
     * con él, para que no quede un cobro vivo de un documento muerto.
     */
    public function execute(
        string $id,
        UpdateStatusClientAdvanceCommand $command,
        ?string $companyId = null,
    ): ClientAdvance {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientAdvanceNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            if ($command->status === 'pending_confirmation') {
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
