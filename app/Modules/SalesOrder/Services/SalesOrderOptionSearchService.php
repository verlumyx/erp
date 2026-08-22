<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\SalesOrder\Commands\SearchSalesOrderCommand;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de pedidos para el select remoto.
 *
 * Es la misma búsqueda del listado más lo que la pantalla que factura un
 * pedido necesita en ese instante: sus líneas activas con el artículo y la
 * unidad. El listado no las carga, por eso no se resuelve en el repositorio.
 */
class SalesOrderOptionSearchService
{
    public function __construct(
        private readonly SalesOrderRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSalesOrderCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing([
            'lines.item',
            'lines.measurementUnit',
        ]);

        return $result;
    }
}
