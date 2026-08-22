<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SalesOrder\Commands\UpdateSalesOrderCommand;
use App\Modules\SalesOrder\Exceptions\SalesOrderNotFoundException;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;

class SalesOrderUpdateService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    /**
     * Un pedido solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo. Al confirmarlo quedan congeladas: ya nada las toca.
     */
    public function execute(string $id, UpdateSalesOrderCommand $command, ?string $companyId = null): SalesOrder
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new SalesOrderNotFoundException;
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
