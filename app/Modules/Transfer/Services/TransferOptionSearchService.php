<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Transfer\Commands\SearchTransferCommand;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Búsqueda de traslados para el select remoto.
 *
 * Es la misma búsqueda del listado más las líneas, que el documento que elige
 * un traslado necesita en el instante en que lo elige: de ellas arma lo que va
 * a hacer con la mercancía sin volver al servidor.
 */
class TransferOptionSearchService
{
    public function __construct(
        private readonly TransferRepositoryInterface $repository,
    ) {}

    /**
     * @return array{ data: mixed[], total: int }
     */
    public function execute(SearchTransferCommand $command): array
    {
        $result = $this->repository->search($command);

        (new Collection($result['data']))->loadMissing([
            'originWarehouse',
            'destinationWarehouse',
            'lines.item',
            'lines.measurementUnit',
        ]);

        return $result;
    }
}
