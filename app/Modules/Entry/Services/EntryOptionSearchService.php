<?php

declare(strict_types=1);

namespace App\Modules\Entry\Services;

use App\Modules\Entry\Commands\SearchEntryCommand;
use App\Modules\Entry\Repositories\Contracts\EntryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de entradas para el select remoto.
 *
 * Es la misma búsqueda del listado más el proveedor, que la pantalla que
 * referencia una entrada necesita en el instante en que la elige para
 * comprobar que es del proveedor de su documento.
 */
class EntryOptionSearchService
{
    public function __construct(
        private readonly EntryRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchEntryCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing(['supplier']);

        return $result;
    }
}
