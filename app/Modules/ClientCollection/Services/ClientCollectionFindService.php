<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\ClientCollection\Exceptions\ClientCollectionNotFoundException;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;

class ClientCollectionFindService
{
    public function __construct(
        private readonly ClientCollectionRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): ClientCollection
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientCollectionNotFoundException;
        }

        return $model;
    }
}
