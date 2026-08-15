<?php

declare(strict_types=1);

namespace App\Modules\Client\Services;

use App\Modules\Client\Commands\SearchClientCommand;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;

class ClientSearchService
{
    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchClientCommand $command): array
    {
        return $this->repository->search($command);
    }
}
