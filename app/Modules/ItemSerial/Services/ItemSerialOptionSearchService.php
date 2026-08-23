<?php

declare(strict_types=1);

namespace App\Modules\ItemSerial\Services;

use App\Modules\ItemSerial\Commands\SearchItemSerialCommand;
use App\Modules\ItemSerial\Repositories\Contracts\ItemSerialRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de series para el select remoto.
 *
 * Es la misma búsqueda del listado más el artículo, que el formulario necesita
 * en el instante en que se elige la serie para armar la etiqueta y validar que
 * corresponde al artículo de la línea.
 */
class ItemSerialOptionSearchService
{
    public function __construct(
        private readonly ItemSerialRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchItemSerialCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing(['item']);

        return $result;
    }
}
