<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

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

        $rates = $this->rates->forDocument(
            $command->companyId,
            $command->currency,
            $command->collectionDate,
            $command->exchangeRateOverride,
        );

        $this->repository->create($command, $rates);

        return $this->repository->findOrFail($command->id);
    }
}
