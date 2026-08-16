<?php

declare(strict_types=1);

namespace App\Modules\Category\Repositories\Contracts;

use App\Modules\Category\Commands\CreateCategoryCommand;
use App\Modules\Category\Commands\SearchCategoryCommand;
use App\Modules\Category\Commands\UpdateCategoryCommand;
use App\Modules\Category\Commands\UpdateStatusCategoryCommand;
use App\Modules\Category\Models\Category;

interface CategoryRepositoryInterface
{
    public function create(CreateCategoryCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Category;

    public function findOrFail(string $id, ?string $companyId = null): Category;

    public function update(Category $model, UpdateCategoryCommand $command): void;

    public function updateStatus(Category $model, UpdateStatusCategoryCommand $command): void;

    /** @return array{ data: Category[], total: int } */
    public function search(SearchCategoryCommand $command): array;
}
