<?php

declare(strict_types=1);

namespace App\Modules\Category\Services;

use App\Modules\Category\Commands\CreateCategoryCommand;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;

class CategoryCreateService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function execute(CreateCategoryCommand $command): Category
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
