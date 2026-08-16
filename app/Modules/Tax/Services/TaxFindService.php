<?php

declare(strict_types=1);

namespace App\Modules\Tax\Services;

use App\Modules\Tax\Exceptions\TaxNotFoundException;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;

class TaxFindService
{
    public function __construct(
        private readonly TaxRepositoryInterface $repository,
    ) {}

    public function execute(string $id, ?string $companyId = null): Tax
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new TaxNotFoundException;
        }

        return $model;
    }
}
