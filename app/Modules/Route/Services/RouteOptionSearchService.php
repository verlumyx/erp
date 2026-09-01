<?php

declare(strict_types=1);

namespace App\Modules\Route\Services;

use App\Modules\Route\Commands\SearchRouteCommand;
use App\Modules\Route\Repositories\Contracts\RouteRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de rutas para el select remoto.
 *
 * Es la misma búsqueda del listado más la bodega y el conductor, que el
 * despacho copia en el instante en que elige la ruta: de ahí saca de dónde sale
 * la carga y quién la lleva, sin volver al servidor.
 */
class RouteOptionSearchService
{
    public function __construct(
        private readonly RouteRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchRouteCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing(['warehouse', 'driver']);

        return $result;
    }
}
