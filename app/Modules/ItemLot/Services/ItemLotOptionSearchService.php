<?php

declare(strict_types=1);

namespace App\Modules\ItemLot\Services;

use App\Modules\ItemLot\Commands\SearchItemLotCommand;
use App\Modules\ItemLot\Repositories\Contracts\ItemLotRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de lotes para el select remoto.
 *
 * Es la misma búsqueda del listado más el artículo, que el formulario necesita
 * en el instante en que se elige el lote para armar la etiqueta y validar que
 * corresponde al artículo de la línea.
 */
class ItemLotOptionSearchService
{
    public function __construct(
        private readonly ItemLotRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchItemLotCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing(['item']);

        return $result;
    }
}
