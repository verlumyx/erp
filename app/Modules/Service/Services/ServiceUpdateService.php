<?php

declare(strict_types=1);

namespace App\Modules\Service\Services;

use App\Modules\Service\Commands\UpdateServiceCommand;
use App\Modules\Service\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;

class ServiceUpdateService
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateServiceCommand $command, ?string $companyId = null): Service
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ServiceNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
