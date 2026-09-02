<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\CreateDispatchCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;

class DispatchCreateService
{
    public function __construct(
        private readonly DispatchRepositoryInterface $repository,
        private readonly DispatchSourceService $source,
        private readonly DispatchCostService $costs,
        private readonly DispatchPricingService $pricing,
    ) {}

    /**
     * El despacho nace en borrador: sus líneas ya están escritas, pero ninguna
     * existencia se ha movido. Es al confirmarlo cuando la mercancía sale.
     *
     * No congela tasas: el despacho no lleva importes en moneda extranjera. Lo
     * único que vale dinero en él es el costo de la mercancía, y ese vive en la
     * moneda en la que la empresa valora sus existencias.
     */
    public function execute(CreateDispatchCommand $command): Dispatch
    {
        $this->source->guard(
            $command->sourceableType,
            $command->sourceableId,
            $command->recipientType,
            $command->recipientId,
            $command->companyId,
            $command->lines,
            $command->id,
        );

        /**
         * El precio no lo decide la pantalla: sale del pedido que se despacha
         * o, sin pedido, del promedio del artículo.
         */
        $lines = $this->pricing->apply($command->companyId, $command->sourceableId, $command->lines);

        $this->repository->create(
            $command,
            $this->costs->resolve($command->companyId, $lines),
            $lines,
        );

        return $this->repository->findOrFail($command->id);
    }
}
