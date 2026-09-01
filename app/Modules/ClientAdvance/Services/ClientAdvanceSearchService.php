<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Services;

use App\Modules\ClientAdvance\Commands\SearchClientAdvanceCommand;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;

class ClientAdvanceSearchService
{
    public function __construct(
        private readonly ClientAdvanceRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchClientAdvanceCommand $command): array
    {
        return $this->repository->search($command);
    }
}
