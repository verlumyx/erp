<?php

declare(strict_types=1);

namespace App\Modules\Sale\Services;

use App\Modules\Sale\Commands\ReactivateSaleCommand;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;

class SaleReactivateService
{
    public function __construct(
        private readonly SaleRepositoryInterface $repository,
    ) {}

    public function execute(ReactivateSaleCommand $command): Sale
    {
        $sale = $this->repository->findOrFail($command->saleId, $command->companyId);

        $this->repository->reactivate($sale, $command);

        return $this->repository->findOrFail($command->saleId, $command->companyId);
    }
}
