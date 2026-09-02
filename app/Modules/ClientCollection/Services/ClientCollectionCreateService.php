<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\ClientCollection\Commands\ClientCollectionApplicationData;
use App\Modules\ClientCollection\Commands\CreateClientCollectionCommand;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;

class ClientCollectionCreateService
{
    public function __construct(
        private readonly ClientCollectionRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly ClientCollectionOriginService $origin,
        private readonly ClientCollectionCreditSourceService $creditSources,
    ) {}

    /**
     * El cobro se valora con la tasa de **su** fecha de cobro, no con la de la
     * factura que cancela: contra esa diferencia sale el diferencial cambiario
     * de cada aplicación. Si falta la tasa del día el cobro no se registra.
     */
    public function execute(CreateClientCollectionCommand $command): ClientCollection
    {
        $this->origin->guardOrigin(
            $command->originType,
            $command->originId,
            $command->clientId,
            $command->companyId,
        );

        $this->origin->guardApplications(
            $command->applications,
            $command->clientId,
            $command->companyId,
        );

        $this->creditSources->guard(
            $command->paymentMethod,
            $command->creditSourceId,
            $command->clientId,
            $command->companyId,
            $this->appliedTotal($command->applications),
        );

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->collectionDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create($command, $rates);

        return $this->repository->findOrFail($command->id);
    }

    /**
     * Lo que el reparto quiere abonar en total. Es el tope contra el que se
     * mide el crédito disponible cuando el cobro no trae dinero.
     *
     * @param  array<int, ClientCollectionApplicationData>  $applications
     */
    private function appliedTotal(array $applications): float
    {
        return round(array_sum(array_map(
            static fn (ClientCollectionApplicationData $row): float => $row->status === 'active'
                ? $row->appliedAmount
                : 0.0,
            $applications,
        )), 2);
    }
}
