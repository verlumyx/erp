<?php

declare(strict_types=1);

namespace App\Modules\Menu\Services;

use App\Modules\Menu\Commands\SearchMenuCommand;
use App\Modules\Menu\Repositories\Contracts\MenuRepositoryInterface;

class MenuSearchService
{
    public function __construct(
        private readonly MenuRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchMenuCommand $command): array
    {
        return $this->repository->search($command);
    }
}
