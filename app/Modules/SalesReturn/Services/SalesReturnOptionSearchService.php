<?php

declare(strict_types=1);

namespace App\Modules\SalesReturn\Services;

use App\Modules\SalesReturn\Commands\SearchSalesReturnCommand;
use App\Modules\SalesReturn\Repositories\Contracts\SalesReturnRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de devoluciones para el select remoto.
 *
 * Es la misma búsqueda del listado más el cliente, que el formulario de la
 * nota de crédito necesita en el instante en que se elige la devolución para
 * comprobar que es del cliente de la nota.
 */
class SalesReturnOptionSearchService
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchSalesReturnCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing(['client']);

        return $result;
    }
}
