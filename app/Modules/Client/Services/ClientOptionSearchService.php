<?php

declare(strict_types=1);

namespace App\Modules\Client\Services;

use App\Modules\Client\Commands\SearchClientCommand;
use App\Modules\Client\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de clientes para el select remoto.
 *
 * Es la misma búsqueda del listado más la relación que el formulario que elige
 * al cliente necesita en ese instante: sus direcciones. El listado no las
 * carga, por eso no se resuelve en el repositorio.
 */
class ClientOptionSearchService
{
    public function __construct(
        private readonly ClientRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchClientCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing('addresses');

        return $result;
    }
}
