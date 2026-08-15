<?php

declare(strict_types=1);

namespace App\Modules\Plan\Services;

use App\Modules\Plan\Commands\CreatePlanCommand;
use App\Modules\Plan\Models\Plan;
use App\Modules\Plan\Repositories\Contracts\PlanRepositoryInterface;

class PlanCreateService
{
    public function __construct(
        private readonly PlanRepositoryInterface $repository,
    ) {}

    public function execute(CreatePlanCommand $command): Plan
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
