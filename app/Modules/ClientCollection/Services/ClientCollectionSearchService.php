<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\ClientCollection\Commands\SearchClientCollectionCommand;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;

class ClientCollectionSearchService
{
    public function __construct(
        private readonly ClientCollectionRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchClientCollectionCommand $command): array
    {
        return $this->repository->search($command);
    }
}
