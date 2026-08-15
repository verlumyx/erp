<?php

declare(strict_types=1);

namespace App\Modules\Service\Services;

use App\Modules\Service\Commands\CreateServiceCommand;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;

class ServiceCreateService
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {}

    public function execute(CreateServiceCommand $command): Service
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
