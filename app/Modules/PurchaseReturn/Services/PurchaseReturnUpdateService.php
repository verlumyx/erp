<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\PurchaseReturn\Commands\UpdatePurchaseReturnCommand;
use App\Modules\PurchaseReturn\Exceptions\PurchaseReturnNotFoundException;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;

class PurchaseReturnUpdateService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly PurchaseReturnLimitsService $limits,
    ) {}

    /**
     * Una devolución solo se edita en borrador, así que cada guardado refresca
     * las tasas del catálogo. Al confirmarla quedan congeladas: es el valor con
     * el que la mercancía salió y ya nada lo recalcula.
     */
    public function execute(string $id, UpdatePurchaseReturnCommand $command, ?string $companyId = null): PurchaseReturn
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new PurchaseReturnNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->limits->guard(
            $command->purchaseInvoiceId,
            $command->supplierId,
            $company,
            $command->lines,
            $model->id,
        );

        $rates = $this->rates->forDocument(
            $company,
            $command->currency,
            $command->returnDate,
            $command->exchangeRateOverride,
        );

        $this->repository->update($model, $command, $rates);

        return $this->repository->findOrFail($id, $companyId);
    }
}
