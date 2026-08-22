<?php

declare(strict_types=1);

namespace App\Modules\Item\Services;

use App\Modules\Item\Commands\SearchItemCommand;
use App\Modules\Item\Repositories\Contracts\ItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de artículos para el select remoto.
 *
 * Es la misma búsqueda del listado más las relaciones que el formulario que
 * elige el artículo necesita en ese instante: sus unidades y sus precios por
 * lista. El listado no las carga, por eso no se resuelve en el repositorio.
 */
class ItemOptionSearchService
{
    public function __construct(
        private readonly ItemRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchItemCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing([
            'units.measurementUnit',
            'prices',
        ]);

        return $result;
    }
}
