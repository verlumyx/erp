<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Services;

use App\Modules\ClientAdvance\Exceptions\ClientAdvanceNotFoundException;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;

class ClientAdvanceFindService
{
    public function __construct(
        private readonly ClientAdvanceRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): ClientAdvance
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientAdvanceNotFoundException;
        }

        return $model;
    }
}
