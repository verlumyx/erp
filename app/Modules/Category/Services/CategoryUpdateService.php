<?php

declare(strict_types=1);

namespace App\Modules\Category\Services;

use App\Modules\Category\Commands\UpdateCategoryCommand;
use App\Modules\Category\Exceptions\CategoryNotFoundException;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;

class CategoryUpdateService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateCategoryCommand $command, ?string $companyId = null): Category
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new CategoryNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
