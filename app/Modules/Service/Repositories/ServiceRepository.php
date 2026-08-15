<?php

declare(strict_types=1);

namespace App\Modules\Service\Repositories;

use App\Modules\Service\Commands\CreateServiceCommand;
use App\Modules\Service\Commands\SearchServiceCommand;
use App\Modules\Service\Commands\UpdateServiceCommand;
use App\Modules\Service\Commands\UpdateStatusServiceCommand;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ServiceRepository extends ServiceFilters implements ServiceRepositoryInterface
{
    public function create(CreateServiceCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            Service::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'name' => $command->name,
                'logo_url' => $command->logoUrl,
                'max_profiles' => $command->maxProfiles,
                'active' => true,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Service
    {
        return Service::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Service
    {
        return Service::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Service $model, UpdateServiceCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'logo_url' => $command->logoUrl,
            'max_profiles' => $command->maxProfiles,
        ]);
    }

    public function updateStatus(Service $model, UpdateStatusServiceCommand $command): void
    {
        $model->update([
            'active' => $command->active,
        ]);
    }

    /**
     * @return array{ data: Service[], total: int }
     */
    public function search(SearchServiceCommand $command): array
    {
        $query = Service::query()
            ->when($command->companyId, fn ($q) => $q->where('company_id', $command->companyId));

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->orderByDesc('created_at')
            ->limit($command->limit)
            ->offset($command->offset)
            ->get();

        return ['data' => $data->all(), 'total' => $total];
    }

    /**
     * Generate the next sequential per-company code (SER000001, SER000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Service::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Service::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Service::CODE_PREFIX))) + 1
            : 1;

        return Service::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
