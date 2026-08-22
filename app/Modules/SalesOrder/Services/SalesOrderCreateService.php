<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SalesOrder\Commands\CreateSalesOrderCommand;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;

class SalesOrderCreateService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    /**
     * Las tasas salen del catálogo, nunca del formulario. Si falta la del día
     * el pedido no se emite: es preferible no poder vender a vender con una
     * tasa inventada.
     */
    public function execute(CreateSalesOrderCommand $command): SalesOrder
    {
        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->orderDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create($command, $rates);

        return $this->repository->findOrFail($command->id);
    }
}
