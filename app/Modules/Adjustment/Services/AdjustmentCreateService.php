<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Commands\CreateAdjustmentCommand;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;

class AdjustmentCreateService
{
    public function __construct(
        private readonly AdjustmentRepositoryInterface $repository,
        private readonly AdjustmentStockService $stock,
        private readonly AdjustmentLimitsService $limits,
    ) {}

    /**
     * El ajuste nace comparando lo contado contra la existencia del momento. Ni
     * esa existencia ni el costo con el que se valora viajan desde la pantalla:
     * se resuelven aquí, que es lo que hace que el documento pruebe algo.
     */
    public function execute(CreateAdjustmentCommand $command): Adjustment
    {
        $stock = $this->stock->resolve($command->companyId, $command->warehouseId, $command->lines);

        $this->limits->guard(
            $command->type,
            $command->direction,
            $command->companyId,
            $command->lines,
            $stock,
        );

        $this->repository->create($command, $stock);

        return $this->repository->findOrFail($command->id);
    }
}
