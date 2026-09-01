<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Services;

use App\Modules\ClientAdvance\Commands\CreateClientAdvanceCommand;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;

class ClientAdvanceCreateService
{
    public function __construct(
        private readonly ClientAdvanceRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly ClientAdvanceOrderService $orders,
    ) {}

    /**
     * El anticipo se valora con la tasa de **su** fecha, no con la del pedido
     * que lo motiva: son dos documentos y cada uno congela la suya. Si falta la
     * tasa del día el anticipo no se registra.
     */
    public function execute(CreateClientAdvanceCommand $command): ClientAdvance
    {
        $this->orders->guardSalesOrder(
            $command->salesOrderId,
            $command->clientId,
            $command->companyId,
        );

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->advanceDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create($command, $rates);

        return $this->repository->findOrFail($command->id);
    }
}
