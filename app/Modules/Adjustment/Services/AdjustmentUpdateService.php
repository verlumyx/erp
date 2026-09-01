<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Commands\UpdateAdjustmentCommand;
use App\Modules\Adjustment\Exceptions\AdjustmentNotFoundException;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;

class AdjustmentUpdateService
{
    public function __construct(
        private readonly AdjustmentRepositoryInterface $repository,
        private readonly AdjustmentStockService $stock,
        private readonly AdjustmentLimitsService $limits,
    ) {}

    /**
     * Un ajuste solo se edita en borrador, así que cada guardado vuelve a leer
     * la existencia: el borrador que se guarda enseña la diferencia de ese
     * instante. La que cuenta se comprueba otra vez al confirmar, que es cuando
     * el ajuste toca el inventario.
     */
    public function execute(string $id, UpdateAdjustmentCommand $command, ?string $companyId = null): Adjustment
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new AdjustmentNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $stock = $this->stock->resolve($company, $command->warehouseId, $command->lines);

        $this->limits->guard(
            $command->type,
            $command->direction,
            $company,
            $command->lines,
            $stock,
        );

        $this->repository->update($model, $command, $stock);

        return $this->repository->findOrFail($id, $companyId);
    }
}
