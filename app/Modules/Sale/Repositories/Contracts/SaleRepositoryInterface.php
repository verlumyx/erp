<?php

declare(strict_types=1);

namespace App\Modules\Sale\Repositories\Contracts;

use App\Modules\Sale\Commands\CancelSaleCommand;
use App\Modules\Sale\Commands\CreateSaleCommand;
use App\Modules\Sale\Commands\ReactivateSaleCommand;
use App\Modules\Sale\Commands\RenewSaleCommand;
use App\Modules\Sale\Commands\SearchSaleCommand;
use App\Modules\Sale\Models\Sale;

interface SaleRepositoryInterface
{
    public function create(CreateSaleCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Sale;

    public function findOrFail(string $id, ?string $companyId = null): Sale;

    public function renew(Sale $model, RenewSaleCommand $command): void;

    public function reactivate(Sale $model, ReactivateSaleCommand $command): void;

    public function cancel(Sale $model, CancelSaleCommand $command): void;

    /** @return array{ data: Sale[], total: int } */
    public function search(SearchSaleCommand $command): array;

    /** @return array{ data: Sale[], total: int } */
    public function searchExpirations(SearchSaleCommand $command): array;

    /**
     * @return array{ expiring_count: int, expiring_amount: float, expired_count: int, expired_amount: float, renewal_rate: float }
     */
    public function summarizeExpirations(SearchSaleCommand $command): array;
}
