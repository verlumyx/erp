<?php

declare(strict_types=1);

namespace App\Modules\Company\Services;

use App\Modules\Company\Commands\SearchCompanyCommand;
use App\Modules\Company\Repositories\Contracts\CompanyRepositoryInterface;

class CompanySearchService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchCompanyCommand $command): array
    {
        return $this->repository->search($command);
    }
}
