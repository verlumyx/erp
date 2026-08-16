<?php

declare(strict_types=1);

namespace App\Modules\ClientType\Repositories;

use App\Modules\ClientType\Commands\CreateClientTypeCommand;
use App\Modules\ClientType\Commands\SearchClientTypeCommand;
use App\Modules\ClientType\Commands\UpdateClientTypeCommand;
use App\Modules\ClientType\Commands\UpdateStatusClientTypeCommand;
use App\Modules\ClientType\Models\ClientType;
use App\Modules\ClientType\Repositories\Contracts\ClientTypeRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ClientTypeRepository extends ClientTypeFilters implements ClientTypeRepositoryInterface
{
    public function create(CreateClientTypeCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            ClientType::create([
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

    public function findById(string $id, ?string $companyId = null): ?ClientType
    {
        return ClientType::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): ClientType
    {
        return ClientType::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(ClientType $model, UpdateClientTypeCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'description' => $command->description,
        ]);
    }

    public function updateStatus(ClientType $model, UpdateStatusClientTypeCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: ClientType[], total: int }
     */
    public function search(SearchClientTypeCommand $command): array
    {
        $query = ClientType::query()
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
     * Generate the next sequential per-company code (TCL000001, TCL000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = ClientType::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', ClientType::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(ClientType::CODE_PREFIX))) + 1
            : 1;

        return ClientType::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
