<?php

declare(strict_types=1);

namespace App\Modules\Permission\Services;

use App\Modules\Permission\Commands\SearchPermissionCommand;
use App\Modules\Permission\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionSearchService
{
    public function __construct(
        private readonly PermissionRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchPermissionCommand $command): array
    {
        return $this->repository->search($command);
    }
}
