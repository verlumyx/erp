<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Services;

use App\Modules\SupplierAdvance\Commands\SearchSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;

class SupplierAdvanceSearchService
{
    public function __construct(
        private readonly SupplierAdvanceRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSupplierAdvanceCommand $command): array
    {
        return $this->repository->search($command);
    }
}
