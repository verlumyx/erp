<?php

declare(strict_types=1);

namespace App\Modules\Client\Repositories;

use App\Modules\Client\Commands\CreateClientCommand;
use App\Modules\Client\Commands\SearchClientCommand;
use App\Modules\Client\Commands\UpdateClientCommand;
use App\Modules\Client\Commands\UpdateStatusClientCommand;
use App\Modules\Client\Models\Client;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ClientRepository extends ClientFilters implements ClientRepositoryInterface
{
    public function create(CreateClientCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            Client::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'name' => $command->name,
                'phone' => $command->phone,
                'email' => $command->email,
                'notes' => $command->notes,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?Client
    {
        return Client::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): Client
    {
        return Client::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(Client $model, UpdateClientCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'phone' => $command->phone,
            'email' => $command->email,
            'notes' => $command->notes,
        ]);
    }

    public function updateStatus(Client $model, UpdateStatusClientCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: Client[], total: int }
     */
    public function search(SearchClientCommand $command): array
    {
        $query = Client::query()
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
     * Generate the next sequential per-company code (CLI000001, CLI000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = Client::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', Client::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(Client::CODE_PREFIX))) + 1
            : 1;

        return Client::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
