<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Transfer\Commands\CreateTransferCommand;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;

class TransferCreateService
{
    public function __construct(
        private readonly TransferRepositoryInterface $repository,
        private readonly TransferCostService $costs,
    ) {}

    /**
     * El traslado nace en borrador: sus líneas ya están escritas, pero ninguna
     * existencia se ha movido. Es al confirmarlo cuando la mercancía sale de la
     * bodega de origen.
     *
     * No congela tasas: un traslado no cambia el valor del inventario, solo su
     * ubicación, así que no hay importe en moneda extranjera que reexpresar.
     */
    public function execute(CreateTransferCommand $command): Transfer
    {
        $this->repository->create(
            $command,
            $this->costs->resolve($command->companyId, $command->originWarehouseId, $command->lines),
        );

        return $this->repository->findOrFail($command->id);
    }
}
