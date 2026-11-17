<?php

declare(strict_types=1);

namespace App\Modules\LoadPlan\Services;

use App\Modules\LoadPlan\Commands\CreateLoadPlanCommand;
use App\Modules\LoadPlan\Models\LoadPlan;
use App\Modules\LoadPlan\Repositories\Contracts\LoadPlanRepositoryInterface;

class LoadPlanCreateService
{
    public function __construct(
        private readonly LoadPlanRepositoryInterface $repository,
    ) {}

    public function execute(CreateLoadPlanCommand $command): LoadPlan
    {
        return $this->repository->create($command);
    }
}
