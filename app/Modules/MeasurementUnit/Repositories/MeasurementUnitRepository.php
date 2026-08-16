<?php

declare(strict_types=1);

namespace App\Modules\MeasurementUnit\Repositories;

use App\Modules\MeasurementUnit\Commands\CreateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Commands\SearchMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Commands\UpdateMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Commands\UpdateStatusMeasurementUnitCommand;
use App\Modules\MeasurementUnit\Models\MeasurementUnit;
use App\Modules\MeasurementUnit\Repositories\Contracts\MeasurementUnitRepositoryInterface;
use Illuminate\Support\Facades\DB;

class MeasurementUnitRepository extends MeasurementUnitFilters implements MeasurementUnitRepositoryInterface
{
    public function create(CreateMeasurementUnitCommand $command): void
    {
        DB::transaction(function () use ($command): void {
            MeasurementUnit::create([
                'id' => $command->id,
                'company_id' => $command->companyId,
                'code' => $this->generateNextCode($command->companyId),
                'name' => $command->name,
                'description' => $command->description,
                'abbreviation' => $command->abbreviation,
                'status' => 'active',
                'created_by' => $command->createdBy,
            ]);
        });
    }

    public function findById(string $id, ?string $companyId = null): ?MeasurementUnit
    {
        return MeasurementUnit::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->find($id);
    }

    public function findOrFail(string $id, ?string $companyId = null): MeasurementUnit
    {
        return MeasurementUnit::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);
    }

    public function update(MeasurementUnit $model, UpdateMeasurementUnitCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'description' => $command->description,
            'abbreviation' => $command->abbreviation,
        ]);
    }

    public function updateStatus(MeasurementUnit $model, UpdateStatusMeasurementUnitCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: MeasurementUnit[], total: int }
     */
    public function search(SearchMeasurementUnitCommand $command): array
    {
        $query = MeasurementUnit::query()
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
     * Generate the next sequential per-company code (UOM000001, UOM000002, …).
     */
    private function generateNextCode(string $companyId): string
    {
        $last = MeasurementUnit::query()
            ->where('company_id', $companyId)
            ->where('code', 'like', MeasurementUnit::CODE_PREFIX.'%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = $last !== null
            ? ((int) substr($last, strlen(MeasurementUnit::CODE_PREFIX))) + 1
            : 1;

        return MeasurementUnit::CODE_PREFIX.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }
}
