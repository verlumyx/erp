<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\UpdateDispatchCommand;
use App\Modules\Dispatch\Exceptions\DispatchNotFoundException;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;

class DispatchUpdateService
{
    public function __construct(
        private readonly DispatchRepositoryInterface $repository,
        private readonly DispatchSourceService $source,
        private readonly DispatchCostService $costs,
        private readonly DispatchPricingService $pricing,
    ) {}

    /**
     * Un despacho solo se edita en borrador, así que cada guardado vuelve a
     * comprobar el pedido y a refrescar el costo de la carga. Al confirmarlo
     * queda congelado con el costo que el kardex le dio a la salida.
     */
    public function execute(string $id, UpdateDispatchCommand $command, ?string $companyId = null): Dispatch
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new DispatchNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->source->guard(
            $command->sourceableType,
            $command->sourceableId,
            $command->recipientType,
            $command->recipientId,
            $company,
            $command->lines,
            $model->id,
        );

        /**
         * El precio no lo decide la pantalla: sale del pedido que se despacha
         * o, sin pedido, del promedio del artículo.
         */
        $lines = $this->pricing->apply($company, $command->sourceableId, $command->lines);

        $this->repository->update(
            $model,
            $command,
            $this->costs->resolve($company, $lines),
            $lines,
        );

        return $this->repository->findOrFail($id, $companyId);
    }
}
