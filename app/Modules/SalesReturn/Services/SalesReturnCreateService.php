<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;
use App\Modules\SalesReturn\Commands\CreateSalesReturnCommand;
use App\Modules\SalesReturn\Models\SalesReturn;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;

class SalesReturnCreateService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly SalesReturnLimitsService $limits,
        private readonly SalesReturnCostService $costs,
    ) {}

    /**
     * La devolución se valora con la tasa de **su** fecha, no con la de la
     * factura que la origina: son dos documentos y cada uno congela la suya. Si
     * falta la tasa del día la devolución no se emite.
     *
     * El costo de reingreso es harina de otro costal: ese sí viene de la venta
     * original, porque es el valor con el que la mercancía salió.
     */
    public function execute(CreateSalesReturnCommand $command): SalesReturn
    {
        $this->limits->guard(
            $command->salesInvoiceId,
            $command->clientId,
            $command->companyId,
            $command->lines,
            $command->id,
        );

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->returnDate,
            $command->exchangeRateOverride,
        );

        $unitCosts = $this->costs->resolve(
            $command->salesInvoiceId,
            $command->companyId,
            $command->lines,
        );

        $this->repository->create($command, $rates, $unitCosts);

        return $this->repository->findOrFail($command->id);
    }
}
