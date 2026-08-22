<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\PurchaseOrder\Commands\CreatePurchaseOrderCommand;
use App\Modules\PurchaseOrder\Models\PurchaseOrder;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;

class PurchaseOrderCreateService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
    ) {}

    /**
     * Las tasas salen del catálogo, nunca del formulario. Si falta la del día
     * la orden no se emite: es preferible no poder comprar a comprar con una
     * tasa inventada.
     */
    public function execute(CreatePurchaseOrderCommand $command): PurchaseOrder
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
