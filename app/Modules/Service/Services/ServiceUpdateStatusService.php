<?php

declare(strict_types=1);

namespace App\Modules\Service\Services;

use App\Modules\Service\Commands\UpdateStatusServiceCommand;
use App\Modules\Service\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;

class ServiceUpdateStatusService
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusServiceCommand $command, ?string $companyId = null): Service
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ServiceNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
