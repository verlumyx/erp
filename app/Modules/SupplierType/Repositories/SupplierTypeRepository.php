<?php

declare(strict_types=1);

namespace App\Modules\SupplierType\Repositories;

use App\Modules\SupplierType\Commands\CreateSupplierTypeCommand;
use App\Modules\SupplierType\Commands\SearchSupplierTypeCommand;
use App\Modules\SupplierType\Commands\UpdateStatusSupplierTypeCommand;
use App\Modules\SupplierType\Commands\UpdateSupplierTypeCommand;
use App\Modules\SupplierType\Models\SupplierType;
use App\Modules\SupplierType\Repositories\Contracts\SupplierTypeRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SupplierTypeRepository extends SupplierTypeFilters implements SupplierTypeRepositoryInterface
{
    public function create(CreateSupplierTypeCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            SupplierType::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'name' => $command->name,
                'description' => $command->description,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?SupplierType
    {
        return SupplierType::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): SupplierType
    {
        return SupplierType::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(SupplierType $model, UpdateSupplierTypeCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'description' => $command->description,
        ]);
    }

    public function updateStatus(SupplierType $model, UpdateStatusSupplierTypeCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: SupplierType[], total: int }
     */
    public function search(SearchSupplierTypeCommand $command): array
    {
        $query = SupplierType::query()
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderBy('name')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Generate the next sequential per-company code (TPR000001, TPR000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = SupplierType::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', SupplierType::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(SupplierType::CODE_PREFIX))) + 1
            : 1;

        return SupplierType::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
