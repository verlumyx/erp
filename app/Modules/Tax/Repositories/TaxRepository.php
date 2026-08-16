<?php

declare(strict_types=1);

namespace App\Modules\Tax\Repositories;

use App\Modules\Tax\Commands\CreateTaxCommand;
use App\Modules\Tax\Commands\SearchTaxCommand;
use App\Modules\Tax\Commands\UpdateStatusTaxCommand;
use App\Modules\Tax\Commands\UpdateTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;
use Illuminate\Support\Facades\DB;

class TaxRepository extends TaxFilters implements TaxRepositoryInterface
{
    public function create(CreateTaxCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            Tax::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'name' => $command->name,
                'description' => $command->description,
                'percentage' => $command->percentage,
                'has_withholding' => $command->hasWithholding,
                'withholding_percentage' => $command->withholdingPercentage,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Tax
    {
        return Tax::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Tax
    {
        return Tax::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Tax $model, UpdateTaxCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'description' => $command->description,
            'percentage' => $command->percentage,
            'has_withholding' => $command->hasWithholding,
            'withholding_percentage' => $command->withholdingPercentage,
        ]);
    }

    public function updateStatus(Tax $model, UpdateStatusTaxCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: Tax[], total: int }
     */
    public function search(SearchTaxCommand $command): array
    {
        $query = Tax::query()
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
     * Generate the next sequential per-company code (IMP000001, IMP000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Tax::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Tax::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Tax::CODE_PREFIX))) + 1
            : 1;

        return Tax::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
