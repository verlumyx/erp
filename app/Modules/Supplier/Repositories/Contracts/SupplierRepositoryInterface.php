<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Repositories\Contracts;

use App\Modules\Supplier\Commands\CreateSupplierCommand;
use App\Modules\Supplier\Commands\SearchSupplierCommand;
use App\Modules\Supplier\Commands\UpdateStatusSupplierCommand;
use App\Modules\Supplier\Commands\UpdateSupplierCommand;
use App\Modules\Supplier\Commands\WriteSupplierBalancesCommand;
use App\Modules\Supplier\Models\Supplier;

interface SupplierRepositoryInterface
{
    public function create(CreateSupplierCommand $command): void;

    public function findById(string $id, ?string $companyId = null): ?Supplier;

    public function findOrFail(string $id, ?string $companyId = null): Supplier;

    public function update(Supplier $model, UpdateSupplierCommand $command): void;

    public function updateStatus(Supplier $model, UpdateStatusSupplierCommand $command): void;

    /**
     * El proveedor con su fila bloqueada, para que dos documentos que mueven
     * sus saldos a la vez no lean el mismo número.
     */
    public function lockById(string $id, ?string $companyId = null): ?Supplier;

    /** Escribe los saldos resueltos. Solo lo llama `SupplierApplyBalanceService`. */
    public function writeBalances(Supplier $model, WriteSupplierBalancesCommand $command): Supplier;

    /** @return array{ data: Supplier[], total: int } */
    public function search(SearchSupplierCommand $command): array;
}
