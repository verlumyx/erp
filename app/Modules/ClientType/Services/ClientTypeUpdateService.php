<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Services;

use App\Modules\ClientType\Commands\UpdateClientTypeCommand;
use App\Modules\ClientType\Exceptions\ClientTypeNotFoundException;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;

class ClientTypeUpdateService
{
    public function __construct(
        private readonly ClientTypeRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateClientTypeCommand $command, ?string $companyId = null): ClientType
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientTypeNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
