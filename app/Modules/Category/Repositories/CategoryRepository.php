<?php

declare(strict_types=1);

namespace App\Modules\Category\Repositories;

use App\Modules\Category\Commands\CreateCategoryCommand;
use App\Modules\Category\Commands\SearchCategoryCommand;
use App\Modules\Category\Commands\UpdateCategoryCommand;
use App\Modules\Category\Commands\UpdateStatusCategoryCommand;
use App\Modules\Category\Models\Category;
use App\Modules\Category\Repositories\Contracts\CategoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CategoryRepository extends CategoryFilters implements CategoryRepositoryInterface
{
    public function create(CreateCategoryCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            Category::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'name' => $command->name,
                'description' => $command->description,
                'order' => $command->order,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Category
    {
        return Category::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Category
    {
        return Category::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Category $model, UpdateCategoryCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'description' => $command->description,
            'order' => $command->order,
        ]);
    }

    public function updateStatus(Category $model, UpdateStatusCategoryCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: Category[], total: int }
     */
    public function search(SearchCategoryCommand $command): array
    {
        $query = Category::query()
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('order')
            ->orderBy('name')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Generate the next sequential per-company code (CAT000001, CAT000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Category::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Category::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Category::CODE_PREFIX))) + 1
            : 1;

        return Category::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
