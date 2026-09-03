<?php

declare(strict_types=1);

namespace App\Modules\SalesOrder\Services;

use App\Modules\Client\Models\Client;
use App\Modules\Dispatch\Commands\CreateDispatchCommand;
use App\Modules\Dispatch\Commands\DispatchLineData;
use App\Modules\Dispatch\Commands\SearchDispatchCommand;
use App\Modules\Dispatch\Commands\UpdateStatusDispatchCommand;
use App\Modules\Dispatch\Models\Dispatch;
use App\Modules\Dispatch\Repositories\Contracts\DispatchRepositoryInterface;
use App\Modules\Dispatch\Services\DispatchCostService;
use App\Modules\Dispatch\Services\DispatchPricingService;
use App\Modules\Item\Models\Item;
use App\Modules\SalesOrder\Models\SalesOrder;
use App\Modules\SalesOrder\Models\SalesOrderLine;
use App\Modules\SalesOrder\Repositories\Contracts\SalesOrderRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * El despacho espejo del pedido de venta: el documento que sí saca la
 * mercancía.
 *
 * Confirmar el pedido no descarga inventario —solo lo compromete—, pero deja
 * escrito lo que hay que sacar: un despacho `DES` en borrador, ya colgado del
 * pedido y con las líneas pendientes copiadas. Quien carga el camión abre ese
 * borrador, completa lo físico —ubicación, lotes, series, lo que de verdad
 * sale— y lo confirma. Es ese confirmar, y no el del pedido, el que toca el
 * kardex y libera la reserva.
 *
 * Un artículo sin existencia —un servicio, una cuota de instalación— no viaja
 * al despacho: no hay nada que cargar en el camión.
 */
class SalesOrderMirrorDispatchService
{

    public function __construct(
        private readonly SalesOrderRepositoryInterface $orders,
        private readonly DispatchRepositoryInterface $dispatches,
        private readonly DispatchPricingService $pricing,
        private readonly DispatchCostService $costs,
    ) {}

    /**
     * Crea el despacho que sacará la mercancía del pedido.
     *
     * Devuelve `null` cuando no hay nada que despachar: un pedido solo de
     * servicios, uno ya despachado por completo, o uno que ya tiene su
     * despacho. Que alguien lo hubiera armado a mano antes de confirmar no es
     * razón para tumbar la confirmación: se respeta el que ya existe.
     */
    public function create(SalesOrder $order): ?Dispatch
    {
        if ($this->liveDispatch($order) instanceof Dispatch) {
            return null;
        }

        $lines = $this->pendingLines($order);

        if ($lines === []) {
            return null;
        }

        $id = (string) Str::uuid7();

        /** El precio no lo decide nadie aquí: sale de la línea del pedido. */
        $priced = $this->pricing->apply((string) $order->company_id, $order->id, $lines);

        $this->dispatches->create(
            new CreateDispatchCommand(
                id: $id,
                companyId: (string) $order->company_id,
                recipientType: Client::MORPH_ALIAS,
                recipientId: (string) $order->client_id,
                warehouseId: (string) $order->warehouse_id,
                dispatchDate: now()->toDateString(),
                createdBy: (string) $order->created_by,
                lines: $lines,
                sourceableType: SalesOrder::MORPH_ALIAS,
                sourceableId: $order->id,
                clientAddressId: $order->client_address_id,
                /** La ruta es planificación del pedido: el despacho la hereda. */
                routeId: $order->route_id,
                notes: "Generado al aprobar el pedido {$order->code}.",
            ),
            $this->costs->resolve((string) $order->company_id, $priced),
            $priced,
        );

        return $this->dispatches->findOrFail($id, $order->company_id);
    }

    /**
     * Anula el despacho en borrador del pedido, si lo hay. Lo llama el pedido
     * al anularse: lo que ya no se va a vender tampoco se va a entregar.
     */
    public function cancel(SalesOrder $order): void
    {
        $dispatch = $this->liveDispatch($order);

        if (! $dispatch instanceof Dispatch || $dispatch->status !== 'draft') {
            return;
        }

        $this->dispatches->updateStatus($dispatch, new UpdateStatusDispatchCommand(status: 'cancelled'));
    }

    /**
     * Un pedido con mercancía ya despachada no se anula por las buenas:
     * primero hay que anular el despacho que la sacó, que es el que sabe
     * deshacer su propio asiento en el kardex.
     *
     * @throws ValidationException
     */
    public function guardCancellable(SalesOrder $order): void
    {
        $dispatch = $this->liveDispatch($order);

        if (! $dispatch instanceof Dispatch || ! in_array($dispatch->status, Dispatch::POSTED_STATUSES, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => "El pedido ya tiene el despacho {$dispatch->code} confirmado: anúlalo primero.",
        ]);
    }

    /**
     * El despacho del pedido que todavía cuenta. Uno anulado no estorba: el
     * pedido puede volver a generar el suyo.
     */
    public function liveDispatch(SalesOrder $order): ?Dispatch
    {
        $result = $this->dispatches->search(new SearchDispatchCommand(
            filters: ['sourceable_id' => $order->id],
            limit: 50,
            companyId: $order->company_id,
        ));

        foreach ($result['data'] as $dispatch) {
            if ($dispatch->status !== 'cancelled') {
                return $dispatch;
            }
        }

        return null;
    }

    /**
     * Lo que el pedido todavía debe entregar, como líneas del despacho.
     *
     * El dinero va en cero a propósito: lo escribe `DispatchPricingService`
     * copiándolo de la línea del pedido. Y la unidad se copia tal cual, porque
     * un despacho saca en la misma unidad en la que se vendió.
     *
     * @return array<int, DispatchLineData>
     */
    private function pendingLines(SalesOrder $order): array
    {
        $lines = [];

        foreach ($this->orders->activeLines($order) as $line) {
            $pending = $this->pendingQuantity($line);

            if ($pending <= 0.0) {
                continue;
            }

            $lines[] = new DispatchLineData(
                id: null,
                itemId: (string) $line->item_id,
                measurementUnitId: (string) $line->measurement_unit_id,
                quantity: $pending,
                unitPrice: 0.0,
                discountPercent: 0.0,
                discountAmount: 0.0,
                taxPercent: 0.0,
                taxAmount: 0.0,
                withholdingPercent: 0.0,
                withholdingAmount: 0.0,
                subtotal: 0.0,
                total: 0.0,
                sourceableType: SalesOrderLine::MORPH_ALIAS,
                sourceableId: $line->id,
            );
        }

        return $lines;
    }

    /**
     * Lo que le queda por salir a la línea. Un artículo sin existencia no sale
     * nunca: el despacho no lo lleva.
     */
    private function pendingQuantity(SalesOrderLine $line): float
    {
        $item = $line->item;

        if ($item instanceof Item && ! $item->movesStock()) {
            return 0.0;
        }

        return max(round((float) $line->quantity - (float) $line->dispatched_quantity, 4), 0.0);
    }
}
