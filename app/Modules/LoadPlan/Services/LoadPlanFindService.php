<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Services;

use App\Modules\LoadPlan\Exceptions\LoadPlanNotFoundException;
use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;

class LoadPlanFindService
{
    public function __construct(
        private readonly LoadPlanRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): LoadPlan
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new LoadPlanNotFoundException;
        }

        return $model;
    }
}
