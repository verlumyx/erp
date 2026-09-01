<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;

class EntrySearchService
{
    public function __construct(
        private readonly EntryRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchEntryCommand $command): array
    {
        return $this->repository->search($command);
    }
}
