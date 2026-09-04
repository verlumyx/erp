<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Commands\UpdateStatusImportCommand;
use App\Modules\Import\Exceptions\ImportNotFoundException;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ImportUpdateStatusService
{
    public function __construct(
        private readonly ImportRepositoryInterface $repository,
        private readonly ImportCostingService $costing,
        private readonly ImportLimitsService $limits,
        private readonly ImportMirrorAdjustmentService $mirror,
    ) {}

    /**
     * El expediente no toca el kardex: lo toca el ajuste de revaluación que
     * genera. Confirmarlo escribe ese ajuste en borrador; anularlo lo retira.
     *
     * Antes de generarlo se vuelve a repartir. Entre el último borrador y la
     * firma la bodega siguió trabajando, y lo que se capitaliza depende de lo
     * que siga en existencia: el número que el ajuste va a escribir tiene que
     * ser el de ahora, no el de cuando alguien guardó.
     */
    public function execute(
        string $id,
        UpdateStatusImportCommand $command,
        ?string $companyId = null,
    ): Import {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ImportNotFoundException;
        }

        DB::transaction(function () use ($model, $command): void {
            /**
             * Se comprueba antes de tocar nada: si el ajuste ya cambió el valor
             * del inventario, el expediente no se anula hasta anularlo.
             */
            if ($command->status === 'cancelled') {
                $this->mirror->guardCancellable($model);
            }

            if ($command->status === 'confirmed') {
                $costing = $this->costing->recost($model);

                $this->limits->guardConfirmable($model, $costing);
                $this->repository->writeCosting($model, $costing);

                $this->mirror->create($model->refresh());
            }

            if ($command->status === 'cancelled') {
                $this->mirror->cancel($model);
            }

            $this->repository->updateStatus($model, $command);
        });

        return $this->repository->findOrFail($id, $companyId);
    }
}
