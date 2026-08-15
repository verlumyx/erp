<?php

declare(strict_types=1);

namespace App\Modules\Sale\Services;

use App\Modules\Sale\Commands\CreateSaleCommand;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;

class SaleCreateService
{
    public function __construct(
        private readonly SaleRepositoryInterface $repository,
    ) {}

    public function execute(CreateSaleCommand $command): Sale
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id, $command->companyId);
    }
}
