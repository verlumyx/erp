<?php

declare(strict_types=1);

namespace App\Modules\Plan\Repositories\Contracts;

use App\Modules\Plan\Commands\CreatePlanCommand;
use App\Modules\Plan\Commands\SearchPlanCommand;
use App\Modules\Plan\Commands\UpdatePlanCommand;
use App\Modules\Plan\Commands\UpdateStatusPlanCommand;
use App\Modules\Plan\Models\Plan;

interface PlanRepositoryInterface
{
    public function create(CreatePlanCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Plan;

    public function findOrFail(string $id, ?string $companyId = null): Plan;

    public function update(Plan $model, UpdatePlanCommand $command): void;

    public function updateStatus(Plan $model, UpdateStatusPlanCommand $command): void;

    /** @return array{ data: Plan[], total: int } */
    public function search(SearchPlanCommand $command): array;
}
