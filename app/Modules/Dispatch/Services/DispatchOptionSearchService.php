<?php

declare(strict_types=1);

namespace App\Modules\Dispatch\Services;

use App\Modules\Dispatch\Commands\SearchDispatchCommand;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de despachos para el select remoto.
 *
 * Es la misma búsqueda del listado más el cliente y las líneas, que el
 * formulario de la factura de venta necesita en el instante en que se elige el
 * despacho: de ellas arma lo que va a facturar sin descargar inventario otra
 * vez.
 */
class DispatchOptionSearchService
{
    public function __construct(
        private readonly DispatchRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchDispatchCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing([
            'client',
            'lines.item',
            'lines.measurementUnit',
        ]);

        return $result;
    }
}
