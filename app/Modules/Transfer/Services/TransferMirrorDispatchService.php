<?php

declare(strict_types=1);

namespace App\Modules\Transfer\Services;

use App\Modules\Dispatch\Commands\CreateDispatchCommand;
use App\Modules\Dispatch\Commands\DispatchLineData;
use App\Modules\Dispatch\Commands\SearchDispatchCommand;
use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Dispatch\Services\DispatchCostService;
use App\Modules\Item\Models\Item;
use App\Modules\Transfer\Models\Transfer;
use App\Modules\Transfer\Models\TransferLine;
use App\Modules\Transfer\Repositories\Contracts\TransferRepositoryInterface;
use App\Modules\Warehouse\Models\Warehouse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * El despacho espejo del traslado: el documento que sí saca la mercancía de la
 * bodega de origen.
 *
 * El traslado dejó de mover el kardex por su cuenta. Confirmarlo escribe lo que
 * hay que sacar —un despacho `DES` en borrador, colgado del traslado y dirigido
 * a la bodega de destino— y ahí se acaba su papel. Quien carga el camión abre
 * ese borrador, elige el lote y la serie de lo que se lleva, y lo confirma: es
 * ese confirmar el que escribe la salida.
 *
 * El destinatario no es un cliente sino una bodega propia, que es justamente
 * para lo que el despacho tiene un destinatario polimórfico.
 *
 * Un artículo sin existencia no viaja: no hay nada que cargar en el camión.
 */
class TransferMirrorDispatchService
{
    /**
     * Tipos de artículo que no llevan existencia y por tanto no se trasladan.
     *
     * @var array<int, string>
     */
    private const NON_STOCKED_TYPES = ['service', 'non_inventoried'];

    public function __construct(
        private readonly TransferRepositoryInterface $transfers,
        private readonly DispatchRepositoryInterface $dispatches,
        private readonly DispatchCostService $costs,
    ) {}

    /**
     * Crea el despacho que sacará la mercancía del origen.
     *
     * Devuelve `null` cuando no hay nada que mover: un traslado solo de
     * servicios, o uno que ya tiene su despacho.
     */
    public function create(Transfer $transfer): ?Dispatch
    {
        if ($this->liveDispatch($transfer) instanceof Dispatch) {
            return null;
        }

        $lines = $this->stockedLines($transfer);

        if ($lines === []) {
            return null;
        }

        $id = (string) Str::uuid7();

        $this->dispatches->create(
            new CreateDispatchCommand(
                id: $id,
                companyId: (string) $transfer->company_id,
                /** Va a otra bodega de la empresa, no a un cliente. */
                recipientType: Warehouse::MORPH_ALIAS,
                recipientId: (string) $transfer->destination_warehouse_id,
                warehouseId: (string) $transfer->origin_warehouse_id,
                dispatchDate: $transfer->transfer_date?->toDateString() ?? now()->toDateString(),
                createdBy: (string) $transfer->created_by,
                lines: $lines,
                sourceableType: Transfer::MORPH_ALIAS,
                sourceableId: $transfer->id,
                routeId: $transfer->route_id,
                driverId: $transfer->driver_id,
                vehiclePlate: $transfer->vehicle_plate,
                notes: "Generado al confirmar el traslado {$transfer->code}.",
            ),
            $this->costs->resolve((string) $transfer->company_id, $lines),
            $lines,
        );

        return $this->dispatches->findOrFail($id, $transfer->company_id);
    }

    /**
     * Anula el despacho en borrador del traslado, si lo hay. Lo llama el
     * traslado al anularse: lo que ya no se mueve tampoco se carga.
     */
    public function cancel(Transfer $transfer): void
    {
        $dispatch = $this->liveDispatch($transfer);

        if (! $dispatch instanceof Dispatch || $dispatch->status !== 'draft') {
            return;
        }

        $this->dispatches->updateStatus($dispatch, new UpdateStatusDispatchCommand(status: 'cancelled'));
    }

    /**
     * Un traslado cuya mercancía ya salió no se anula por las buenas: primero
     * hay que anular el despacho que la sacó, que es el que sabe deshacer su
     * propio asiento en el kardex.
     *
     * @throws ValidationException
     */
    public function guardCancellable(Transfer $transfer): void
    {
        $dispatch = $this->liveDispatch($transfer);

        if (! $dispatch instanceof Dispatch || ! in_array($dispatch->status, Dispatch::POSTED_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "El traslado ya tiene el despacho {$dispatch->code} confirmado: anúlalo primero.",
        ]);
    }

    /**
     * El despacho del traslado que todavía cuenta. Uno anulado no estorba: el
     * traslado puede volver a generar el suyo.
     */
    public function liveDispatch(Transfer $transfer): ?Dispatch
    {
        $result = $this->dispatches->search(new SearchDispatchCommand(
            filters: ['sourceable_id' => $transfer->id],
            limit: 50,
            companyId: $transfer->company_id,
        ));

        foreach ($result['data'] as $dispatch) {
            if ($dispatch->status !== 'cancelled') {
                return $dispatch;
            }
        }

        return null;
    }

    /**
     * Lo que el traslado mueve, como líneas del despacho.
     *
     * Van sin lote y sin serie a propósito: el traslado ya no los captura, y el
     * número de la caja lo lee quien carga el camión.
     *
     * @return array<int, DispatchLineData>
     */
    private function stockedLines(Transfer $transfer): array
    {
        $lines = [];

        foreach ($this->transfers->activeLines($transfer) as $line) {
            if (! $this->movesStock($line)) {
                continue;
            }

            $lines[] = new DispatchLineData(
                id: null,
                itemId: (string) $line->item_id,
                measurementUnitId: (string) $line->measurement_unit_id,
                quantity: round((float) $line->quantity, 4),
                unitPrice: 0.0,
                discountPercent: 0.0,
                discountAmount: 0.0,
                taxPercent: 0.0,
                taxAmount: 0.0,
                withholdingPercent: 0.0,
                withholdingAmount: 0.0,
                subtotal: 0.0,
                total: 0.0,
                sourceableType: TransferLine::MORPH_ALIAS,
                sourceableId: $line->id,
            );
        }

        return $lines;
    }

    /** Un artículo sin existencia no se traslada: no hay saldo que mover. */
    private function movesStock(TransferLine $line): bool
    {
        $item = $line->item;

        if ($item instanceof Item && in_array($item->type, self::NON_STOCKED_TYPES, true)) {
            return false;
        }

        return round((float) $line->quantity, 4) > 0;
    }
}
