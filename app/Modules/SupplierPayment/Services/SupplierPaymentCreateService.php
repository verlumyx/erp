<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SupplierPayment\Commands\CreateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;

class SupplierPaymentCreateService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly SupplierPaymentOriginService $origin,
    ) {}

    /**
     * El pago se valora con la tasa de **su** fecha de pago, no con la de la
     * factura que cancela: contra esa diferencia sale el diferencial cambiario
     * de cada aplicación. Si falta la tasa del día el pago no se registra.
     */
    public function execute(CreateSupplierPaymentCommand $command): SupplierPayment
    {
        $this->origin->guardOrigin(
            $command->originType,
            $command->originId,
            $command->supplierId,
            $command->companyId,
        );

        $this->origin->guardApplications(
            $command->applications,
            $command->supplierId,
            $command->companyId,
        );

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->paymentDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create($command, $rates);

        return $this->repository->findOrFail($command->id);
    }
}
