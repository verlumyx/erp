<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Services;

use App\Modules\ClientType\Commands\SearchClientTypeCommand;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;

class ClientTypeSearchService
{
    public function __construct(
        private readonly ClientTypeRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchClientTypeCommand $command): array
    {
        return $this->repository->search($command);
    }
}
