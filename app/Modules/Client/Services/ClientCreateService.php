<?php

declare(strict_types=1);

namespace App\Modules\Client\Services;

use App\Modules\Client\Commands\CreateClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;

class ClientCreateService
{
    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    public function execute(CreateClientCommand $command): Client
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
