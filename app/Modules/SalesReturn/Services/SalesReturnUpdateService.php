<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SalesReturn\Commands\UpdateSalesReturnCommand;
use App\Modules\SalesReturn\Exceptions\SalesReturnNotFoundException;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;

class SalesReturnUpdateService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly SalesReturnLimitsService $limits,
        private readonly SalesReturnCostService $costs,
    ) {}

    /**
     * Una devolución solo se edita en borrador, así que cada guardado refresca
     * las tasas del catálogo y el costo de reingreso. Al confirmarla quedan
     * congelados: es el valor con el que la mercancía volvió y ya nada lo
     * recalcula.
     */
    public function execute(string $id, UpdateSalesReturnCommand $command, ?string $companyId = null): SalesReturn
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesReturnNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->limits->guard(
            $command->salesInvoiceId,
            $command->clientId,
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

        $unitCosts = $this->costs->resolve(
            $command->salesInvoiceId,
            $company,
            $command->lines,
        );

        $this->repository->update($model, $command, $rates, $unitCosts);

        return $this->repository->findOrFail($id, $companyId);
    }
}
