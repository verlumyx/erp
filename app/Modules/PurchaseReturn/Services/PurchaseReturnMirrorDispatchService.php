<?php

declare(strict_types=1);

namespace App\Modules\PurchaseReturn\Services;

use App\Modules\Dispatch\Commands\CreateDispatchCommand;
use App\Modules\Dispatch\Commands\DispatchLineData;
use App\Modules\Dispatch\Commands\SearchDispatchCommand;
use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Dispatch\Services\DispatchCostService;
use App\Modules\Item\Models\Item;
use App\Modules\PurchaseReturn\Models\PurchaseReturn;
use App\Modules\PurchaseReturn\Models\PurchaseReturnLine;
use App\Modules\PurchaseReturn\Repositories\Contracts\PurchaseReturnRepositoryInterface;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * El despacho espejo de la devolución de compra: el documento que sí saca la
 * mercancía que vuelve al proveedor.
 *
 * La devolución es el acuerdo —qué se devuelve, por qué y cuánto se acredita—,
 * no la salida física. Confirmarla apunta el cupo en la factura y deja escrito
 * lo que hay que sacar: un despacho `DES` en borrador, colgado de la devolución
 * y dirigido al proveedor. Quien prepara el retiro abre ese borrador, elige la
 * ubicación, el lote y la serie de lo que se lleva, y lo confirma: es ese
 * confirmar, y no el de la devolución, el que toca el kardex.
 *
 * El destinatario no es un cliente sino el proveedor, que es justamente para lo
 * que el despacho tiene un destinatario polimórfico.
 *
 * El despacho sale de la bodega de la **cabecera** de la devolución: un
 * despacho descarga de una sola bodega, y esa es la que la devolución declara.
 *
 * Un artículo sin existencia —un servicio, un flete facturado en la misma
 * compra— no viaja al despacho: no hay nada que devolver físicamente.
 */
class PurchaseReturnMirrorDispatchService
{
    public function __construct(
        private readonly PurchaseReturnRepositoryInterface $returns,
        private readonly DispatchRepositoryInterface $dispatches,
        private readonly DispatchCostService $costs,
    ) {}

    /**
     * Crea el despacho que sacará la mercancía devuelta.
     *
     * Devuelve `null` cuando no hay nada que sacar: una devolución solo de
     * servicios, o una que ya tiene su despacho. Que alguien lo hubiera armado
     * a mano antes de confirmar no es razón para tumbar la confirmación: se
     * respeta el que ya existe.
     */
    public function create(PurchaseReturn $return): ?Dispatch
    {
        if ($this->liveDispatch($return) instanceof Dispatch) {
            return null;
        }

        $lines = $this->stockedLines($return);

        if ($lines === []) {
            return null;
        }

        $id = (string) Str::uuid7();

        $this->dispatches->create(
            new CreateDispatchCommand(
                id: $id,
                companyId: (string) $return->company_id,
                /** La mercancía vuelve a quien la vendió, no a un cliente. */
                recipientType: Supplier::MORPH_ALIAS,
                recipientId: (string) $return->supplier_id,
                warehouseId: (string) $return->warehouse_id,
                dispatchDate: $return->return_date?->toDateString() ?? now()->toDateString(),
                createdBy: (string) $return->created_by,
                lines: $lines,
                sourceableType: PurchaseReturn::MORPH_ALIAS,
                sourceableId: $return->id,
                carrier: $return->carrier,
                trackingNumber: $return->tracking_number,
                notes: "Generado al confirmar la devolución {$return->code}.",
            ),
            $this->costs->resolve((string) $return->company_id, $lines),
            $lines,
        );

        return $this->dispatches->findOrFail($id, $return->company_id);
    }

    /**
     * Anula el despacho en borrador de la devolución, si lo hay. Lo llama la
     * devolución al anularse: lo que ya no se devuelve tampoco se retira.
     */
    public function cancel(PurchaseReturn $return): void
    {
        $dispatch = $this->liveDispatch($return);

        if (! $dispatch instanceof Dispatch || $dispatch->status !== 'draft') {
            return;
        }

        $this->dispatches->updateStatus($dispatch, new UpdateStatusDispatchCommand(status: 'cancelled'));
    }

    /**
     * Una devolución cuya mercancía ya salió no se anula por las buenas:
     * primero hay que anular el despacho que la sacó, que es el que sabe
     * deshacer su propio asiento en el kardex.
     *
     * @throws ValidationException
     */
    public function guardCancellable(PurchaseReturn $return): void
    {
        $dispatch = $this->liveDispatch($return);

        if (! $dispatch instanceof Dispatch || ! in_array($dispatch->status, Dispatch::POSTED_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "La devolución ya tiene el despacho {$dispatch->code} confirmado: anúlalo primero.",
        ]);
    }

    /**
     * El despacho de la devolución que todavía cuenta. Uno anulado no estorba:
     * la devolución puede volver a generar el suyo.
     */
    public function liveDispatch(PurchaseReturn $return): ?Dispatch
    {
        $result = $this->dispatches->search(new SearchDispatchCommand(
            filters: ['sourceable_id' => $return->id],
            limit: 50,
            companyId: $return->company_id,
        ));

        foreach ($result['data'] as $dispatch) {
            if ($dispatch->status !== 'cancelled') {
                return $dispatch;
            }
        }

        return null;
    }

    /**
     * Lo que la devolución saca, como líneas del despacho.
     *
     * Van sin lote y sin serie a propósito: la devolución ya no los captura, y
     * el número de la caja lo lee quien prepara el retiro. El dinero va en cero
     * porque lo escribe `DispatchPricingService` —una guía no factura—.
     *
     * @return array<int, DispatchLineData>
     */
    private function stockedLines(PurchaseReturn $return): array
    {
        $lines = [];

        foreach ($this->returns->activeLines($return) as $line) {
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
                sourceableType: PurchaseReturnLine::MORPH_ALIAS,
                sourceableId: $line->id,
            );
        }

        return $lines;
    }

    /** Un artículo sin existencia no se devuelve: no hay saldo que mover. */
    private function movesStock(PurchaseReturnLine $line): bool
    {
        $item = $line->item;

        if ($item instanceof Item && ! $item->movesStock()) {
            return false;
        }

        return round((float) $line->quantity, 4) > 0;
    }
}
