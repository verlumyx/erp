<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Commands\SearchImportCommand;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;

class ImportSearchService
{
    public function __construct(
        private readonly ImportRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchImportCommand $command): array
    {
        return $this->repository->search($command);
    }
}
