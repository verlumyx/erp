<?php

declare(strict_types=1);

namespace App\Modules\ClientCollection\Services;

use App\Modules\ClientCollection\Commands\ClientCollectionApplicationData;
use App\Modules\ClientCollection\Commands\UpdateClientCollectionCommand;
use App\Modules\ClientCollection\Exceptions\ClientCollectionNotFoundException;
use App\Modules\ClientCollection\Models\ClientCollection;
use App\Modules\ClientCollection\Repositories\Contracts\ClientCollectionRepositoryInterface;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;

class ClientCollectionUpdateService
{
    public function __construct(
        private readonly ClientCollectionRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly ClientCollectionOriginService $origin,
        private readonly ClientCollectionCreditSourceService $creditSources,
    ) {}

    /**
     * Un cobro solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo. Al confirmarlo quedan congeladas: el dinero entró y
     * ya nada las recalcula.
     */
    public function execute(string $id, UpdateClientCollectionCommand $command, ?string $companyId = null): ClientCollection
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientCollectionNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        /**
         * El origen no se reelige, pero sí se vuelve a comprobar: cambiar de
         * cliente con una factura de origen de otro dejaría el cobro cruzado.
         */
        $this->origin->guardOrigin(
            $model->origin_type,
            $model->origin_id,
            $command->clientId,
            $company,
        );

        $this->origin->guardApplications(
            $command->applications,
            $command->clientId,
            $company,
        );

        $this->creditSources->guard(
            $command->paymentMethod,
            $command->creditSourceId,
            $command->clientId,
            $company,
            $this->appliedTotal($command->applications),
        );

        $rates = $this->rates->forDocument(
            $company,
            $command->currency,
            $command->collectionDate,
            $command->exchangeRateOverride,
        );

        $this->repository->update($model, $command, $rates);

        return $this->repository->findOrFail($id, $companyId);
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
