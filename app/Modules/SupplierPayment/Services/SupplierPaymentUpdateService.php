<?php

declare(strict_types=1);

namespace App\Modules\SupplierPayment\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SupplierPayment\Commands\SupplierPaymentApplicationData;
use App\Modules\SupplierPayment\Commands\UpdateSupplierPaymentCommand;
use App\Modules\SupplierPayment\Exceptions\SupplierPaymentNotFoundException;
use App\Modules\SupplierPayment\Models\SupplierPayment;
use App\Modules\SupplierPayment\Repositories\Contracts\SupplierPaymentRepositoryInterface;

class SupplierPaymentUpdateService
{
    public function __construct(
        private readonly SupplierPaymentRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly SupplierPaymentOriginService $origin,
        private readonly SupplierPaymentCreditSourceService $creditSources,
    ) {}

    /**
     * Un pago solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo. Al confirmarlo quedan congeladas: el dinero salió y
     * ya nada las recalcula.
     */
    public function execute(string $id, UpdateSupplierPaymentCommand $command, ?string $companyId = null): SupplierPayment
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierPaymentNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        /**
         * El origen no se reelige, pero sí se vuelve a comprobar: cambiar de
         * proveedor con una factura de origen de otro dejaría el pago cruzado.
         */
        $this->origin->guardOrigin(
            $model->origin_type,
            $model->origin_id,
            $command->supplierId,
            $company,
        );

        $this->origin->guardApplications(
            $command->applications,
            $command->supplierId,
            $company,
        );

        $this->creditSources->guard(
            $command->paymentMethod,
            $command->creditSourceId,
            $command->supplierId,
            $company,
            $this->appliedTotal($command->applications),
        );

        $rates = $this->rates->forDocument(
            $company,
            $command->currency,
            $command->paymentDate,
            $command->exchangeRateOverride,
        );

        $this->repository->update($model, $command, $rates);

        return $this->repository->findOrFail($id, $companyId);
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
