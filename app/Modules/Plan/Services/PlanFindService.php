<?php

declare(strict_types=1);

namespace App\Modules\Plan\Services;

use App\Modules\Plan\Exceptions\PlanNotFoundException;
use App\Modules\Plan\Models\Plan;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;

class PlanFindService
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Plan
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PlanNotFoundException;
        }

        return $model;
    }
}
