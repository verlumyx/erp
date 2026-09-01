<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\Route\Exceptions\RouteNotFoundException;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;

class RouteFindService
{
    public function __construct(
        private readonly RouteRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Route
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new RouteNotFoundException;
        }

        return $model;
    }
}
