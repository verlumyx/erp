<?php

declare(strict_types=1);

namespace App\Modules\Report\Services;

use App\Modules\Sale\Commands\SearchSaleCommand;
use App\Modules\Sale\Repositories\Contracts\SaleRepositoryInterface;

class ExpirationSummaryService
{
    public function __construct(
        private readonly SaleRepositoryInterface $repository,
    ) {}

    /**
     * Totaliza las métricas del reporte de vencimientos (por vencer, vencidas y
     * tasa de renovación) de una compañía según los filtros del command.
     *
     * @return array{ expiring_count: int, expiring_amount: float, expired_count: int, expired_amount: float, renewal_rate: float }
     */
    public function execute(SearchSaleCommand $command): array
    {
        return $this->repository->summarizeExpirations($command);
    }
}
