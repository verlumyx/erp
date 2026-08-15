<?php

declare(strict_types=1);

namespace App\Modules\Client\Services;

use App\Modules\Client\Commands\UpdateStatusClientCommand;
use App\Modules\Client\Exceptions\ClientNotFoundException;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;

class ClientUpdateStatusService
{
    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusClientCommand $command, ?string $companyId = null): Client
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
