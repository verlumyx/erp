<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\Route\Commands\UpdateStatusRouteCommand;
use App\Modules\Route\Exceptions\RouteNotFoundException;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;

class RouteUpdateStatusService
{
    public function __construct(
        private readonly RouteRepositoryInterface $repository,
    ) {}

    /**
     * Activar o desactivar la ruta. No mueve nada: la ruta planifica el viaje,
     * y quien toca el inventario es el despacho que sale por ella.
     */
    public function execute(
        string $id,
        UpdateStatusRouteCommand $command,
        ?string $companyId = null,
    ): Route {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new RouteNotFoundException;
        }

        $this->repository->updateStatus($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
