<?php

declare(strict_types=1);

namespace App\Modules\Account\Services;

use App\Modules\Account\Commands\SearchAccountCommand;
use App\Modules\Account\Repositories\Contracts\AccountRepositoryInterface;

class AccountSearchService
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchAccountCommand $command): array
    {
        return $this->repository->search($command);
    }
}
