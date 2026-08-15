<?php

declare(strict_types=1);

namespace App\Modules\Role\Services;

use App\Modules\Role\Commands\SearchRoleCommand;
use App\Modules\Role\Repositories\Contracts\RoleRepositoryInterface;

class RoleSearchService
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchRoleCommand $command): array
    {
        return $this->repository->search($command);
    }
}
