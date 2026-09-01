<?php

declare(strict_types=1);

namespace App\Modules\Adjustment\Services;

use App\Modules\Adjustment\Exceptions\AdjustmentNotFoundException;
use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Adjustment\Repositories\Contracts\AdjustmentRepositoryInterface;

class AdjustmentFindService
{
    public function __construct(
        private readonly AdjustmentRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Adjustment
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new AdjustmentNotFoundException;
        }

        return $model;
    }
}
