<?php

declare(strict_types=1);

namespace App\Modules\Client\Services;

use App\Modules\Client\Commands\UpdateClientCommand;
use App\Modules\Client\Exceptions\ClientNotFoundException;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;

class ClientUpdateService
{
    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateClientCommand $command, ?string $companyId = null): Client
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
