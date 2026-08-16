<?php

declare(strict_types=1);

namespace App\Modules\Warehouse\Services;

use App\Modules\Warehouse\Commands\SearchWarehouseCommand;
use App\Modules\Warehouse\Repositories\Contracts\WarehouseRepositoryInterface;

class WarehouseSearchService
{
    public function __construct(
        private readonly WarehouseRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchWarehouseCommand $command): array
    {
        return $this->repository->search($command);
    }
}
