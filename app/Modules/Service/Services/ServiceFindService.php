<?php

declare(strict_types=1);

namespace App\Modules\Service\Services;

use App\Modules\Service\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;

class ServiceFindService
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Service
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ServiceNotFoundException;
        }

        return $model;
    }
}
