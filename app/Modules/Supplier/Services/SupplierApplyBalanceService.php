<?php

declare(strict_types=1);

namespace App\Modules\Supplier\Services;

use App\Modules\Supplier\Commands\ApplySupplierBalanceCommand;
use App\Modules\Supplier\Commands\WriteSupplierBalancesCommand;
use App\Modules\Supplier\Exceptions\SupplierNotFoundException;
use App\Modules\Supplier\Models\Supplier;
use App\Modules\Supplier\Repositories\Contracts\SupplierRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * El único camino que mueve `current_balance` y `advance_balance` de un
 * proveedor.
 *
 * Lo llaman las facturas cuando generan deuda y los pagos, anticipos y notas
 * de crédito cuando la cancelan, siempre con el signo que corresponda. La
 * transacción es anidable: llamado desde el documento que la mueve se suma a
 * la transacción abierta como savepoint.
 */
class SupplierApplyBalanceService
{
    public function __construct(
        private readonly SupplierRepositoryInterface $repository,
    ) {}

    public function execute(ApplySupplierBalanceCommand $command): Supplier
    {
        return DB::transaction(function () use ($command): Supplier {
            $supplier = $this->repository->lockById($command->supplierId, $command->companyId);

            if ($supplier === null) {
                throw new SupplierNotFoundException;
            }

            return $this->repository->writeBalances($supplier, new WriteSupplierBalancesCommand(
                currentBalance: round((float) $supplier->current_balance + $command->currentBalanceDelta, 2),
                advanceBalance: round((float) $supplier->advance_balance + $command->advanceBalanceDelta, 2),
            ));
        });
    }
}
