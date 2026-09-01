<?php

declare(strict_types=1);

namespace App\Modules\ClientAdvance\Services;

use App\Modules\ClientAdvance\Commands\UpdateClientAdvanceCommand;
use App\Modules\ClientAdvance\Exceptions\ClientAdvanceNotFoundException;
use App\Modules\ClientAdvance\Models\ClientAdvance;
use App\Modules\ClientAdvance\Repositories\Contracts\ClientAdvanceRepositoryInterface;
use App\Modules\ExchangeRate\Services\Contracts\DocumentRatesResolverInterface;

class ClientAdvanceUpdateService
{
    public function __construct(
        private readonly ClientAdvanceRepositoryInterface $repository,
        private readonly DocumentRatesResolverInterface $rates,
        private readonly ClientAdvanceOrderService $orders,
    ) {}

    /**
     * Un anticipo solo se edita en borrador, así que cada guardado refresca las
     * tasas del catálogo. Aprobado queda comprometido y su cobro espejo copia
     * esos importes: de ahí en adelante nada los recalcula.
     */
    public function execute(string $id, UpdateClientAdvanceCommand $command, ?string $companyId = null): ClientAdvance
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new ClientAdvanceNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->orders->guardSalesOrder(
            $command->salesOrderId,
            $command->clientId,
            $company,
        );

        $rates = $this->rates->forDocument(
            $company,
            $command->currency,
            $command->advanceDate,
            $command->exchangeRateOverride,
        );

        $this->repository->update($model, $command, $rates);

        return $this->repository->findOrFail($id, $companyId);
    }
}
