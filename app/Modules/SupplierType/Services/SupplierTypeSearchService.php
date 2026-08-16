<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Services;

use App\Modules\SupplierType\Commands\SearchSupplierTypeCommand;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;

class SupplierTypeSearchService
{
    public function __construct(
        private readonly SupplierTypeRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSupplierTypeCommand $command): array
    {
        return $this->repository->search($command);
    }
}
