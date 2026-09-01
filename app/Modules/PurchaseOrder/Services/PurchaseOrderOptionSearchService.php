<?php

declare(strict_types=1);

namespace App\Modules\PurchaseOrder\Services;

use App\Modules\PurchaseOrder\Commands\SearchPurchaseOrderCommand;
use App\Modules\PurchaseOrder\Repositories\Contracts\PurchaseOrderRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de órdenes de compra para el select remoto.
 *
 * Es la misma búsqueda del listado más lo que la pantalla que recibe una orden
 * necesita en ese instante: sus líneas activas con el artículo y la unidad, de
 * las que se arma la entrada sin una segunda ida al servidor. El listado no las
 * carga, por eso no se resuelve en el repositorio.
 */
class PurchaseOrderOptionSearchService
{
    public function __construct(
        private readonly PurchaseOrderRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchPurchaseOrderCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing([
            'lines.item',
            'lines.measurementUnit',
        ]);

        return $result;
    }
}
