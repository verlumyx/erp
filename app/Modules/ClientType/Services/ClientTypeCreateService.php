<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Services;

use App\Modules\ClientType\Commands\CreateClientTypeCommand;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;

class ClientTypeCreateService
{
    public function __construct(
        private readonly ClientTypeRepositoryInterface $repository,
    ) {}

    public function execute(CreateClientTypeCommand $command): ClientType
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
