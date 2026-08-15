<?php

declare(strict_types=1);

namespace App\Modules\Company\Services;

use App\Modules\Company\Exceptions\CompanyNotFoundException;
use App\Modules\Company\Models\Company;
use App\Modules\Company\Repositories\Contracts\CompanyRepositoryInterface;

class CompanyFindService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $repository,
    ) {}

    public function execute(string $id): Company
    {
        $model = $this->repository->findById($id);

        if ($model === null) {
            throw new CompanyNotFoundException;
        }

        return $model;
    }
}
