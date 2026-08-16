<?php

declare(strict_types=1);

namespace App\Modules\Tax\Services;

use App\Modules\Tax\Commands\CreateTaxCommand;
use App\Modules\Tax\Models\Tax;
use App\Modules\Tax\Repositories\Contracts\TaxRepositoryInterface;

class TaxCreateService
{
    public function __construct(
        private readonly TaxRepositoryInterface $repository,
    ) {}

    public function execute(CreateTaxCommand $command): Tax
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
