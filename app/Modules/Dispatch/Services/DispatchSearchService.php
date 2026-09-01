<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\SearchDispatchCommand;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;

class DispatchSearchService
{
    public function __construct(
        private readonly DispatchRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchDispatchCommand $command): array
    {
        return $this->repository->search($command);
    }
}
