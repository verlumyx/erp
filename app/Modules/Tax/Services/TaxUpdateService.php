<?php

declare(strict_types=1);

namespace App\Modules\Tax\Services;

use App\Modules\Tax\Commands\UpdateTaxCommand;
use App\Modules\Tax\Exceptions\TaxNotFoundException;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;

class TaxUpdateService
{
    public function __construct(
        private readonly TaxRepositoryInterface $repository,
    ) {}

    public function execute(string $id, UpdateTaxCommand $command, ?string $companyId = null): Tax
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new TaxNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
