<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\PurchaseReturn\Commands\SearchPurchaseReturnCommand;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de devoluciones para el select remoto.
 *
 * Es la misma búsqueda del listado más el proveedor, que el formulario de la
 * nota de crédito necesita en el instante en que se elige la devolución para
 * comprobar que es del proveedor de la nota.
 */
class PurchaseReturnOptionSearchService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchPurchaseReturnCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing(['supplier']);

        return $result;
    }
}
