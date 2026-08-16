<?php

declare(strict_types=1);

namespace App\Modules\Tax\Services;

use App\Modules\Tax\Commands\SearchTaxCommand;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;

class TaxSearchService
{
    public function __construct(
        private readonly TaxRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchTaxCommand $command): array
    {
        return $this->repository->search($command);
    }
}
