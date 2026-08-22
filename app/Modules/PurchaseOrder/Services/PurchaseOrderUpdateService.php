<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\PurchaseOrder\Commands\UpdatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Exceptions\PurchaseOrderNotFoundException;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;

class PurchaseOrderUpdateService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    /**
     * Una orden solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo. Al confirmarla quedan congeladas: ya nada las toca.
     */
    public function execute(string $id, UpdatePurchaseOrderCommand $command, ?string $companyId = null): PurchaseOrder
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseOrderNotFoundException;
        }

        $rates = $this->rates->forDocument(
            $companyId ?? $model->company_id,
            $command->currency,
            $command->orderDate,
            $command->exchangeRateOverride,
        );

        $this->repository->update($model, $command, $rates);

        return $this->repository->findOrFail($id, $companyId);
    }
}
