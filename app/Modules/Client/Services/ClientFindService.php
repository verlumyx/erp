<?php

declare(strict_types=1);

namespace App\Modules\Client\Services;

use App\Modules\Client\Exceptions\ClientNotFoundException;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;

class ClientFindService
{
    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Client
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientNotFoundException;
        }

        return $model;
    }
}
