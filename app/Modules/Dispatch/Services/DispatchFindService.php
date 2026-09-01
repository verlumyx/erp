<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Exceptions\DispatchNotFoundException;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;

class DispatchFindService
{
    public function __construct(
        private readonly DispatchRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Dispatch
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new DispatchNotFoundException;
        }

        return $model;
    }
}
