<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Services;

use App\Modules\ClientType\Commands\UpdateStatusClientTypeCommand;
use App\Modules\ClientType\Exceptions\ClientTypeNotFoundException;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;

class ClientTypeUpdateStatusService
{
    public function __construct(
        private readonly ClientTypeRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateStatusClientTypeCommand $command, ?string $companyId = null): ClientType
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientTypeNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
