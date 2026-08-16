<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Repositories\Contracts;

use App\Modules\SalesOrder\Commands\CreateSalesOrderCommand;
use App\Modules\SalesOrder\Commands\SearchSalesOrderCommand;
use App\Modules\SalesOrder\Commands\UpdateSalesOrderCommand;
use App\Modules\SalesOrder\Commands\UpdateStatusSalesOrderCommand;
use App\Modules\SalesOrder\Models\SalesOrder;

interface SalesOrderRepositoryInterface
{
    public function create(CreateSalesOrderCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?SalesOrder;

    public function findOrFail(string $id, ?string $companyId = null): SalesOrder;

    public function update(SalesOrder $model, UpdateSalesOrderCommand $command): void;

    public function updateStatus(SalesOrder $model, UpdateStatusSalesOrderCommand $command): void;

    /** @return array{ data: SalesOrder[], total: int } */
    public function search(SearchSalesOrderCommand $command): array;
}
