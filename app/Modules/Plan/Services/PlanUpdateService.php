<?php

declare(strict_types=1);

namespace App\Modules\Plan\Services;

use App\Modules\Plan\Commands\UpdatePlanCommand;
use App\Modules\Plan\Exceptions\PlanNotFoundException;
use App\Modules\Plan\Models\Plan;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;

class PlanUpdateService
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdatePlanCommand $command, ?string $companyId = null): Plan
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PlanNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
