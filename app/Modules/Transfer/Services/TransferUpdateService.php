<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Transfer\Commands\UpdateTransferCommand;
use App\Modules\Transfer\Exceptions\TransferNotFoundException;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;

class TransferUpdateService
{
    public function __construct(
        private readonly TransferRepositoryInterface $repository,
        private readonly TransferCostService $costs,
    ) {}

    /**
     * Un traslado solo se edita en borrador, así que cada guardado refresca el
     * costo con el que la mercancía viajaría hoy. Al confirmarlo queda
     * congelado con el que el kardex le dio a la salida, y con ese —no con el
     * suyo— la recibe el destino.
     */
    public function execute(string $id, UpdateTransferCommand $command, ?string $companyId = null): Transfer
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new TransferNotFoundException;
        }

        $company = $companyId ?? $model->company_id;

        $this->repository->update(
            $model,
            $command,
            $this->costs->resolve($company, $command->originWarehouseId, $command->lines),
        );

        return $this->repository->findOrFail($id, $companyId);
    }
}
