<?php

declare(strict_types=1);

namespace App\Modules\Service\Services;

use App\Modules\Service\Commands\SearchServiceCommand;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;

class ServiceSearchService
{
    public function __construct(
        private readonly ServiceRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchServiceCommand $command): array
    {
        return $this->repository->search($command);
    }
}
