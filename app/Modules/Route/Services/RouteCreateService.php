<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\Route\Commands\CreateRouteCommand;
use App\Modules\Route\Models\Route;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;

class RouteCreateService
{
    public function __construct(
        private readonly RouteRepositoryInterface $repository,
    ) {}

    /**
     * La ruta nace activa y vacía de paradas: los clientes fijos son la
     * plantilla, y las visitas de un día concreto las genera después
     * `RoutePlanService`.
     */
    public function execute(CreateRouteCommand $command): Route
    {
        $this->repository->create($command);

        return $this->repository->findOrFail($command->id);
    }
}
