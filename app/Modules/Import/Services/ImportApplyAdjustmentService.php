<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Adjustment\Models\Adjustment;
use App\Modules\Import\Commands\SearchImportCommand;
use App\Modules\Import\Models\Import;
use App\Modules\Import\Repositories\Contracts\ImportRepositoryInterface;

/**
 * El cierre del expediente, escrito por el ajuste que lo ejecuta.
 *
 * Cerrarlo no es una decisión de la pantalla: el expediente estima, y quien
 * cierra el número es el ajuste al aplicarse contra la existencia del momento.
 * Es el mismo mecanismo con el que la entrada avisa al traslado
 * (`TransferApplyProgressService`) y el despacho al pedido de venta.
 */
class ImportApplyAdjustmentService
{
    public function __construct(
        private readonly ImportRepositoryInterface $repository,
    ) {}

    /**
     * El ajuste ya reexpresó el costo. Lo llama el ajuste al confirmarse, y con
     * `$settled = false` cuando ese ajuste se anula: el expediente vuelve a
     * estar a la espera de una firma.
     */
    public function markSettled(Adjustment $adjustment, bool $settled = true): void
    {
        $import = $this->importOf($adjustment);

        if (! $import instanceof Import || $import->status === 'cancelled') {
            return;
        }

        $this->repository->writeSettlement($import, $settled);
    }

    /**
     * El expediente que generó el ajuste, si lo hay. Un ajuste registrado a
     * mano no cuelga de ninguno.
     */
    private function importOf(Adjustment $adjustment): ?Import
    {
        $result = $this->repository->search(new SearchImportCommand(
            filters: ['adjustment_id' => $adjustment->id],
            limit: 1,
            companyId: $adjustment->company_id,
        ));

        return $result['data'][0] ?? null;
    }
}
