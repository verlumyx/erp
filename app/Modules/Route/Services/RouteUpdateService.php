<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\Route\Commands\UpdateRouteCommand;
use App\Modules\Route\Exceptions\RouteNotFoundException;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;

class RouteUpdateService
{
    public function __construct(
        private readonly RouteRepositoryInterface $repository,
    ) {}

    /**
     * La ruta se edita siempre: es un maestro, no un documento. Cambiar la
     * plantilla no toca las paradas ya generadas —esas son el recorrido de un
     * día que ya se planificó—, solo los recorridos que se planifiquen después.
     */
    public function execute(string $id, UpdateRouteCommand $command, ?string $companyId = null): Route
    {
        $model = $this->repository->findById($id, $companyId);

        if ($model === null) {
            throw new RouteNotFoundException;
        }

        $this->repository->update($model, $command);

        return $this->repository->findOrFail($id, $companyId);
    }
}
