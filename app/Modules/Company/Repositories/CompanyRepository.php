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
            'rif' => $command->rif,
            'address' => $command->address,
            'fiscal_address' => $command->fiscalAddress,
            'phone' => $command->phone,
            'taxpayer_type' => $command->taxpayerType ?? 'ordinary',
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
        $attributes = [
            'name' => $command->name,
            'description' => $command->description,
        ];

        if ($command->rif !== null) {
            $attributes['rif'] = $command->rif;
        }
        if ($command->address !== null) {
            $attributes['address'] = $command->address;
        }
        if ($command->fiscalAddress !== null) {
            $attributes['fiscal_address'] = $command->fiscalAddress;
        }
        if ($command->phone !== null) {
            $attributes['phone'] = $command->phone;
        }
        if ($command->taxpayerType !== null) {
            $attributes['taxpayer_type'] = $command->taxpayerType;
        }

        $model->update($attributes);
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

        $data = $query->orderByDesc('created_at')->limit($command->limit)->offset($command->offset)->get();

        return ['data' => $data->all(), 'total' => $total];
    }
}
