<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Transfer\Commands\WriteTransferLineCostCommand;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;

/**
 * El avance del traslado, escrito por los documentos que lo ejecutan.
 *
 * El traslado ya no mueve mercancía: la saca el despacho y la mete la entrada.
 * Pero sigue siendo el documento que ordena el viaje, así que tiene que saber
 * en qué punto está. Estos son los dos únicos momentos en que alguien se lo
 * dice, y los dos llegan desde fuera del módulo.
 *
 * Es el mismo mecanismo con el que el despacho avisa al pedido de venta
 * (`SalesOrderApplyDispatchService`) y la entrada a la orden de compra
 * (`PurchaseOrderApplyReceiptService`).
 */
class TransferApplyProgressService
{
    public function __construct(
        private readonly TransferRepositoryInterface $repository,
    ) {}

    /**
     * La mercancía salió del origen. Lo llama el despacho al confirmarse, y con
     * `$shipped = false` cuando ese despacho se anula y la carga vuelve.
     */
    public function markShipped(Transfer $transfer, ?string $sentBy = null, bool $shipped = true): void
    {
        if (! $shipped) {
            $this->repository->writeShipment($transfer, null);
            $transfer->update(['transfer_status' => 'pending', 'sent_by' => null]);

            return;
        }

        $this->repository->writeShipment($transfer, $sentBy ?? $transfer->created_by);
    }

    /**
     * La mercancía llegó al destino. Lo llama la entrada al confirmarse, y con
     * `$arrived = false` cuando esa entrada se anula: el viaje vuelve a estar
     * en camino.
     */
    public function markArrived(
        Transfer $transfer,
        string $receivedDate,
        ?string $receivedBy = null,
        bool $arrived = true,
    ): void {
        if (! $arrived) {
            $transfer->update([
                'transfer_status' => 'in_transit',
                'status' => 'confirmed',
                'received_date' => null,
                'received_by' => null,
            ]);

            return;
        }

        $this->repository->writeArrival($transfer, $receivedDate, $receivedBy);
    }

    /**
     * El costo con el que la mercancía salió de verdad, congelado en la línea
     * del traslado. Lo escribe el despacho al asentar su salida: hasta ese
     * momento la línea llevaba el promedio estimado del origen.
     */
    public function writeLineCost(TransferLine $line, float $unitCost): void
    {
        $this->repository->writeLineCost($line, new WriteTransferLineCostCommand(unitCost: $unitCost));
    }
}
