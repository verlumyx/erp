<?php

declare(strict_types=1);

namespace App\Modules\User\Services;

use App\Modules\User\Commands\SearchUserCommand;
use App\Modules\User\Repositories\Contracts\UserRepositoryInterface;

class UserSearchService
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchUserCommand $command): array
    {
        return $this->repository->search($command);
    }
}
