<?php

declare(strict_types=1);

namespace App\Modules\SupplierAdvance\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SupplierAdvance\Commands\UpdateSupplierAdvanceCommand;
use App\Modules\SupplierAdvance\Exceptions\SupplierAdvanceNotFoundException;
use App\Modules\SupplierAdvance\Models\SupplierAdvance;
use App\Modules\SupplierAdvance\Repositories\Contracts\SupplierAdvanceRepositoryInterface;

class SupplierAdvanceUpdateService
{
    public function __construct(
        private readonly SupplierAdvanceRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly SupplierAdvanceOrderService $orders,
    ) {}

    /**
     * Un anticipo solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo. Aprobado queda comprometido y su pago espejo copia
     * esos importes: de ahí en adelante nada los recalcula.
     */
    public function execute(string $id, UpdateSupplierAdvanceCommand $command, ?string $companyId = null): SupplierAdvance
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SupplierAdvanceNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->orders->guardPurchaseOrder(
            $command->purchaseOrderId,
            $command->supplierId,
            $company,
        );

        $rates = $this->rates->forDocument(
            $company,
            $command->currency,
            $command->advanceDate,
            $command->exchangeRateOverride,
        );

        $this->repository->update($model, $command, $rates);

        return $this->repository->findOrFail($id, $companyId);
    }
}
