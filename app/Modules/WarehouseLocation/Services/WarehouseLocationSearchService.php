<?php

declare(strict_types=1);

namespace App\Modules\WarehouseLocation\Services;

use App\Modules\WarehouseLocation\Commands\SearchWarehouseLocationCommand;
use App\Modules\WarehouseLocation\Repositories\Contracts\WarehouseLocationRepositoryInterface;

class WarehouseLocationSearchService
{
    public function __construct(
        private readonly WarehouseLocationRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchWarehouseLocationCommand $command): array
    {
        return $this->repository->search($command);
    }
}
