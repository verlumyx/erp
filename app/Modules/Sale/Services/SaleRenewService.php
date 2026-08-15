<?php

declare(strict_types=1);

namespace App\Modules\Sale\Services;

use App\Modules\Sale\Commands\RenewSaleCommand;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;

class SaleRenewService
{
    public function __construct(
        private readonly SaleRepositoryInterface $repository,
    ) {}

    public function execute(RenewSaleCommand $command): Sale
    {
        $sale = $this->repository->findOrFail($command->saleId, $command->companyId);

        $this->repository->renew($sale, $command);

        return $this->repository->findOrFail($command->saleId, $command->companyId);
    }
}
