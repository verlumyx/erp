<?php

declare(strict_types=1);

namespace App\Modules\Company\Repositories\Contracts;

use App\Modules\Company\Commands\CreateCompanyCommand;
use App\Modules\Company\Commands\SearchCompanyCommand;
use App\Modules\Company\Commands\UpdateCompanyCommand;
use App\Modules\Company\Commands\UpdateStatusCompanyCommand;
use App\Modules\Company\Models\Company;

interface CompanyRepositoryInterface
{
    public function create(CreateCompanyCommand $command): void;

    public function findById(string $id): ?Company;

    public function findOrFail(string $id): Company;

    public function update(Company $model, UpdateCompanyCommand $command): void;

    public function updateStatus(Company $model, UpdateStatusCompanyCommand $command): void;

    /** @return array{ data: Company[], total: int } */
    public function search(SearchCompanyCommand $command): array;
}
