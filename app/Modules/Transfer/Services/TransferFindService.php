<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Transfer\Exceptions\TransferNotFoundException;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;

class TransferFindService
{
    public function __construct(
        private readonly TransferRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Transfer
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new TransferNotFoundException;
        }

        return $model;
    }
}
