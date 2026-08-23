<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SupplierAdvance\Commands\CreateSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;

class SupplierAdvanceCreateService
{
    public function __construct(
        private readonly SupplierAdvanceRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly SupplierAdvanceOrderService $orders,
    ) {}

    /**
     * El anticipo se valora con la tasa de **su** fecha, no con la de la orden
     * que lo motiva: son dos documentos y cada uno congela la suya. Si falta la
     * tasa del día el anticipo no se registra.
     */
    public function execute(CreateSupplierAdvanceCommand $command): SupplierAdvance
    {
        $this->orders->guardPurchaseOrder(
            $command->purchaseOrderId,
            $command->supplierId,
            $command->companyId,
        );

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->advanceDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create($command, $rates);

        return $this->repository->findOrFail($command->id);
    }
}
