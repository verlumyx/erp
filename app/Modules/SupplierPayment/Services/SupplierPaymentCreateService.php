<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SupplierPayment\Commands\SupplierPaymentApplicationData;
use App\Modules\SupplierPayment\Commands\CreateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;

class SupplierPaymentCreateService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly SupplierPaymentOriginService $origin,
        private readonly SupplierPaymentCreditSourceService $creditSources,
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

        $this->creditSources->guard(
            $command->paymentMethod,
            $command->creditSourceId,
            $command->supplierId,
            $command->companyId,
            $this->appliedTotal($command->applications),
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

    /**
     * Lo que el reparto quiere abonar en total. Es el tope contra el que se
     * mide el crédito disponible cuando el pago no saca dinero.
     *
     * @param  array<int, SupplierPaymentApplicationData>  $applications
     */
    private function appliedTotal(array $applications): float
    {
        return round(array_sum(array_map(
            static fn (SupplierPaymentApplicationData $row): float => $row->status === 'active'
                ? $row->appliedAmount
                : 0.0,
            $applications,
        )), 2);
    }
}
