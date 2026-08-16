<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\Supplier\Commands\SearchSupplierCommand;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;

class SupplierSearchService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSupplierCommand $command): array
    {
        return $this->repository->search($command);
    }
}
