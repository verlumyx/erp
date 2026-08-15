<?php

declare(strict_types=1);

namespace App\Modules\Company\Repositories;

use App\Modules\Company\Commands\CreateCompanyCommand;
use App\Modules\Company\Commands\SearchCompanyCommand;
use App\Modules\Company\Commands\UpdateCompanyCommand;
use App\Modules\Company\Commands\UpdateStatusCompanyCommand;
use App\Modules\Company\Models\Company;
use App\Modules\Company\Repositories\Contracts\CompanyRepositoryInterface;

class CompanyRepository extends CompanyFilters implements CompanyRepositoryInterface
{
    public function create(CreateCompanyCommand $command): void
    {
        Company::create([
            'id' => $command->id,
            'name' => $command->name,
            'description' => $command->description,
            'status' => 'active',
            'created_by' => $command->createdBy,
        ]);
    }

    public function findById(string $id): ?Company
    {
        return Company::find($id);
    }

    public function findOrFail(string $id): Company
    {
        return Company::findOrFail($id);
    }

    public function update(Company $model, UpdateCompanyCommand $command): void
    {
        $model->update([
            'name' => $command->name,
            'description' => $command->description,
        ]);
    }

    public function updateStatus(Company $model, UpdateStatusCompanyCommand $command): void
    {
        $model->update([
            'status' => $command->status,
        ]);
    }

    /**
     * @return array{ data: Company[], total: int }
     */
    public function search(SearchCompanyCommand $command): array
    {
        $query = Company::query();

        $query = $this->apply($query, $command->filters);

        $total = $query->count();

        $data = $query->limit($command->limit)->offset($command->offset)->get();

        return ['data' => $data->all(), 'total' => $total];
    }
}
