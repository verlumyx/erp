<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\PurchaseReturn\Commands\CreatePurchaseReturnCommand;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;

class PurchaseReturnCreateService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly PurchaseReturnLimitsService $limits,
        private readonly PurchaseReturnPricingService $pricing,
    ) {}

    /**
     * La devolución se valora con la tasa de **su** fecha, no con la de la
     * factura que la origina: son dos documentos y cada uno congela la suya. Si
     * falta la tasa del día la devolución no se emite.
     */
    public function execute(CreatePurchaseReturnCommand $command): PurchaseReturn
    {
        $this->limits->guard(
            $command->purchaseInvoiceId,
            $command->supplierId,
            $command->companyId,
            $command->lines,
            $command->id,
        );

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->returnDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create(
            $command,
            $rates,
            /** El precio no lo decide la pantalla: sale de la línea facturada. */
            $this->pricing->apply($command->companyId, $command->purchaseInvoiceId, $command->lines),
        );

        return $this->repository->findOrFail($command->id);
    }
}
