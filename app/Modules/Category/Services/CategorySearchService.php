<?php

declare(strict_types=1);

namespace App\Modules\Category\Services;

use App\Modules\Category\Commands\SearchCategoryCommand;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;

class CategorySearchService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchCategoryCommand $command): array
    {
        return $this->repository->search($command);
    }
}
