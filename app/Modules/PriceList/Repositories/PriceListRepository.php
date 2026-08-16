<?php

declare(strict_types=1);

namespace App\Modules\PriceList\Repositories;

use App\Modules\PriceList\Commands\CreatePriceListCommand;
use App\Modules\PriceList\Commands\SearchPriceListCommand;
use App\Modules\PriceList\Commands\UpdatePriceListCommand;
use App\Modules\PriceList\Commands\UpdateStatusPriceListCommand;
use App\Modules\PriceList\Models\PriceList;
use App\Modules\PriceList\Repositories\Contracts\PriceListRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PriceListRepository extends PriceListFilters implements PriceListRepositoryInterface
{
    public function create(CreatePriceListCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            PriceList::create([
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

    public function findById(string $id, ?string $companyId = null): ?PriceList
    {
        return PriceList::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): PriceList
    {
        return PriceList::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(PriceList $model, UpdatePriceListCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'description' => $command->description,
        ]);
    }

    public function updateStatus(PriceList $model, UpdateStatusPriceListCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: PriceList[], total: int }
     */
    public function search(SearchPriceListCommand $command): array
    {
        $query = PriceList::query()
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
     * Generate the next sequential per-company code (PRL000001, PRL000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = PriceList::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', PriceList::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(PriceList::CODE_PREFIX))) + 1
            : 1;

        return PriceList::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
