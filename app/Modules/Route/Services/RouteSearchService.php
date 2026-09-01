<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\Route\Commands\SearchRouteCommand;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;

class RouteSearchService
{
    public function __construct(
        private readonly RouteRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchRouteCommand $command): array
    {
        return $this->repository->search($command);
    }
}
